<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCertification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployeeCertificationController extends Controller
{
    /**
     * Certification Training Matrix — Brenda 2026-05-01.
     *
     *   "We will need a certification training matrix"
     *
     * Pivot view: rows = active employees, columns = every distinct
     * certification name on file, cells = the latest expiry date for
     * that (employee, cert) pair with a color-coded status badge.
     *
     * Use cases this serves:
     *   - "Who's good for OSHA 10?" → scan the OSHA 10 column
     *   - "What's about to expire?" → all yellow/red cells across the grid
     *   - "Does my crane crew have current riggers?" → filter by craft, scan
     *
     * Status legend:
     *   green   = valid, expiry > 30 days away (or no expiry on file)
     *   yellow  = expiring within 30 days
     *   red     = expired
     *   gray —  = not held by this employee
     */
    public function matrix(Request $request): View
    {
        $employees = Employee::query()
            ->when($request->filled('craft_id'), fn ($q) => $q->where('craft_id', $request->craft_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status), fn ($q) => $q->where('status', 'active'))
            ->with(['craft', 'certifications' => fn ($q) => $q->orderBy('expiry_date', 'desc')])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Distinct cert names across all currently-shown employees, sorted
        // alphabetically. Empty result is handled cleanly by the blade.
        $certNames = $employees
            ->flatMap(fn ($e) => $e->certifications->pluck('name'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Pre-compute the (employee_id, cert_name) → certification map so
        // the blade doesn't run nested ->first() filters per cell.
        $matrix = [];
        foreach ($employees as $emp) {
            foreach ($emp->certifications as $cert) {
                $key = $emp->id . '|' . $cert->name;
                // Keep the most-recent (highest expiry_date, falling back
                // to most-recent issue_date) when the same cert appears twice.
                if (! isset($matrix[$key])
                    || ($cert->expiry_date && (! $matrix[$key]->expiry_date || $cert->expiry_date->gt($matrix[$key]->expiry_date)))) {
                    $matrix[$key] = $cert;
                }
            }
        }

        return view('certifications.matrix', [
            'employees' => $employees,
            'certNames' => $certNames,
            'matrix'    => $matrix,
            'crafts'    => \App\Models\Craft::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters'   => $request->only(['craft_id', 'status']),
        ]);
    }

    /**
     * Export the certification matrix as a polished .xlsx workbook.
     *
     * Brenda 2026-05-01: "I have to build one in excel to send my safety
     * department" — instead of her hand-building one every time, give her
     * a one-click download that matches the on-screen matrix layout.
     *
     * Layout: rows = employees, columns = every cert type on file, header
     * row gets the company brand color, cells use the same Valid/Soon/
     * Expired/Missing color logic as the web view. A summary row at the
     * top counts each status. Frozen header + first column so a 50-cert
     * roster still navigates cleanly in Excel.
     */
    public function matrixExcel(Request $request)
    {
        // Re-use the same data fetch as the web matrix so they always agree.
        $employees = Employee::query()
            ->when($request->filled('craft_id'), fn ($q) => $q->where('craft_id', $request->craft_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status), fn ($q) => $q->where('status', 'active'))
            ->with(['craft', 'certifications' => fn ($q) => $q->orderBy('expiry_date', 'desc')])
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        $certNames = $employees
            ->flatMap(fn ($e) => $e->certifications->pluck('name'))
            ->filter()->unique()->sort()->values();

        $matrix = [];
        foreach ($employees as $emp) {
            foreach ($emp->certifications as $cert) {
                $key = $emp->id . '|' . $cert->name;
                if (! isset($matrix[$key])
                    || ($cert->expiry_date && (! $matrix[$key]->expiry_date || $cert->expiry_date->gt($matrix[$key]->expiry_date)))) {
                    $matrix[$key] = $cert;
                }
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Certification Matrix');

        // ─── Top-of-file metadata + summary banner ────────────────────────
        $companyName = \App\Models\Setting::get('company_name', 'BAK Construction');
        $sheet->setCellValue('A1', $companyName);
        $sheet->setCellValue('A2', 'Certification Training Matrix');
        $sheet->setCellValue('A3', 'Generated ' . now()->format('M j, Y g:i A T'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('B91C1C');
        $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('6B7280');

        // Status counts across the matrix
        $counts = ['valid' => 0, 'expiring_soon' => 0, 'expired' => 0];
        foreach ($matrix as $cert) { $counts[$cert->status]++; }
        $missingCount = ($employees->count() * $certNames->count()) - count($matrix);

        $sheet->setCellValue('A5', 'Valid');         $sheet->setCellValue('B5', $counts['valid']);
        $sheet->setCellValue('C5', 'Expiring ≤30d'); $sheet->setCellValue('D5', $counts['expiring_soon']);
        $sheet->setCellValue('E5', 'Expired');       $sheet->setCellValue('F5', $counts['expired']);
        $sheet->setCellValue('G5', 'Not on file');   $sheet->setCellValue('H5', $missingCount);
        foreach (['A5', 'C5', 'E5', 'G5'] as $c) {
            $sheet->getStyle($c)->getFont()->setBold(true);
        }
        $sheet->getStyle('A5:H5')->getFont()->setSize(10);

        // ─── Matrix header row ───────────────────────────────────────────
        $headerRow = 7;
        $sheet->setCellValue("A{$headerRow}", 'Employee #');
        $sheet->setCellValue("B{$headerRow}", 'Last Name');
        $sheet->setCellValue("C{$headerRow}", 'First Name');
        $sheet->setCellValue("D{$headerRow}", 'Craft');
        $col = 5; // E
        foreach ($certNames as $name) {
            $addr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($addr . $headerRow, $name);
            $sheet->getColumnDimension($addr)->setWidth(18);
            $col++;
        }
        $lastCol = $col - 1;

        // Header style: dark navy fill, white text, bold, wrap
        $headerRange = 'A' . $headerRow . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol) . $headerRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '111827']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(36);

        // Sticky pane: freeze first 4 metadata columns + header row
        $sheet->freezePane('E' . ($headerRow + 1));

        // ─── Body rows ────────────────────────────────────────────────────
        $row = $headerRow + 1;
        foreach ($employees as $emp) {
            $sheet->setCellValue("A{$row}", $emp->employee_number ?? '—');
            $sheet->setCellValue("B{$row}", $emp->last_name);
            $sheet->setCellValue("C{$row}", $emp->first_name);
            $sheet->setCellValue("D{$row}", $emp->craft->name ?? '—');

            $colIdx = 5;
            foreach ($certNames as $name) {
                $cert = $matrix[$emp->id . '|' . $name] ?? null;
                $addr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . $row;

                if (! $cert) {
                    $sheet->setCellValue($addr, '—');
                    $sheet->getStyle($addr)->applyFromArray([
                        'font' => ['color' => ['rgb' => 'CBD5E1']],
                        'alignment' => ['horizontal' => 'center'],
                    ]);
                } else {
                    $cellValue = match ($cert->status) {
                        'expired'       => 'EXPIRED' . ($cert->expiry_date ? ' ' . $cert->expiry_date->format('m/d/Y') : ''),
                        'expiring_soon' => 'SOON · ' . ($cert->expiry_date?->format('m/d/Y') ?? ''),
                        default         => $cert->expiry_date ? $cert->expiry_date->format('m/d/Y') : 'Valid (no expiry)',
                    };
                    $sheet->setCellValue($addr, $cellValue);

                    $colors = match ($cert->status) {
                        'expired'       => ['fill' => 'FECACA', 'font' => '991B1B'],
                        'expiring_soon' => ['fill' => 'FEF3C7', 'font' => '92400E'],
                        default         => ['fill' => 'D1FAE5', 'font' => '065F46'],
                    };
                    $sheet->getStyle($addr)->applyFromArray([
                        'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => $colors['fill']]],
                        'font'      => ['color' => ['rgb' => $colors['font']], 'bold' => $cert->status !== 'valid'],
                        'alignment' => ['horizontal' => 'center'],
                    ]);
                }

                $colIdx++;
            }
            $row++;
        }

        // Outer borders + zebra-stripe-friendly border for the body
        if ($row > $headerRow + 1) {
            $bodyRange = 'A' . ($headerRow + 1) . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol) . ($row - 1);
            $sheet->getStyle($bodyRange)->getBorders()->getAllBorders()
                ->setBorderStyle('thin')->getColor()->setRGB('E5E7EB');
        }

        // Column widths for the metadata columns
        $sheet->getColumnDimension('A')->setWidth(13);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);

        // Stream the file to the browser
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'cert-matrix-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'certification_number' => 'nullable|string|max:100',
            'issuing_authority' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:10240', // 10MB
        ]);

        $fileData = [];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $folder = 'certifications/' . $employee->id;
            $path = $file->store($folder, 'documents');
            $fileData = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ];
        }

        $cert = $employee->certifications()->create(
            array_merge($validated, $fileData, ['uploaded_by' => auth()->id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'Certification added.',
            'certification' => $cert,
        ], 201);
    }

    public function update(Request $request, Employee $employee, EmployeeCertification $certification): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'certification_number' => 'nullable|string|max:100',
            'issuing_authority' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:10240',
        ]);

        if ($request->hasFile('file')) {
            // Delete old file
            if ($certification->file_path) {
                Storage::disk('documents')->delete($certification->file_path);
            }
            $file = $request->file('file');
            $folder = 'certifications/' . $employee->id;
            $path = $file->store($folder, 'documents');
            $validated['file_path'] = $path;
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_type'] = $file->getClientMimeType();
            $validated['file_size'] = $file->getSize();
        }

        $certification->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Certification updated.',
            'certification' => $certification->fresh(),
        ]);
    }

    public function destroy(Employee $employee, EmployeeCertification $certification): JsonResponse
    {
        if ($certification->file_path) {
            Storage::disk('documents')->delete($certification->file_path);
        }
        $certification->delete();

        return response()->json(['success' => true, 'message' => 'Certification deleted.']);
    }

    public function download(EmployeeCertification $certification)
    {
        if (!$certification->file_path || !Storage::disk('documents')->exists($certification->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('documents')->download($certification->file_path, $certification->file_name);
    }
}
