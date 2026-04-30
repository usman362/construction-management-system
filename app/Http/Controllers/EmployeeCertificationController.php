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
