<?php

namespace App\Http\Controllers;

use App\Models\CostCode;
use App\Models\Craft;
use App\Models\Employee;
use App\Models\Estimate;
use App\Models\EstimateLine;
use App\Models\Project;
use App\Models\ProjectBillableRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles CSV imports and template downloads for bulk-entry flows.
 *
 * All imports:
 *   - Expect the first row to be the header row (header names match $columns below).
 *   - Match existing records on a natural key (employee_number, estimate_line index,
 *     or project + craft + effective_date for billable rates) so re-uploading the
 *     same file updates instead of duplicating.
 *   - Return to the referer with session('import_result') summarising created /
 *     updated / skipped rows plus per-row errors.
 */
class ImportController extends Controller
{
    // ─── Employees ────────────────────────────────────────────────────────

    private const EMPLOYEE_COLUMNS = [
        'employee_number', 'legacy_employee_id', 'legacy_position', 'legacy_craft',
        'first_name', 'middle_name', 'last_name', 'email', 'phone',
        'address_1', 'address_2', 'city', 'state', 'zip',
        'home_phone', 'work_cell', 'personal_cell',
        'craft_name', 'role', 'employee_type', 'department', 'classification', 'union',
        'is_supervisor', 'certified_pay',
        'pay_cycle', 'pay_type',
        'hourly_rate', 'overtime_rate', 'billable_rate', 'burden_rate',
        'work_comp_code', 'suta_state', 'state_tax', 'city_tax',
        'hire_date', 'start_date', 'rehire_date', 'term_date', 'term_reason',
        'status',
    ];

    public function employeeTemplate(): StreamedResponse
    {
        return $this->streamCsv(
            'employees_import_template.csv',
            self::EMPLOYEE_COLUMNS,
            [
                [
                    'E013', 'JEG2723', 'CROPER', 'Crane Operator',
                    'James', 'E', 'Gilmer', 'jgilmer@example.com', '(251) 555-0100',
                    '702 Boyd Jones Rd', '', 'Citronelle', 'AL', '36522',
                    '', '(251) 232-4130', '',
                    'Operator', 'field', 'Operator', 'CRANE', '', '',
                    '0', '0',
                    'weekly', 'hourly',
                    '32.50', '48.75', '78.00', '0.45',
                    '9534AL', 'LA', 'AL', '',
                    '2012-09-25', '2012-09-25', '', '', '',
                    'active',
                ],
            ]
        );
    }

    public function employeeImport(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->parseCsv($request->file('file')->getRealPath());
        if (empty($rows)) {
            return back()->with('import_result', $result + ['errors' => [['row' => 0, 'message' => 'CSV file is empty.']]]);
        }

        $header = array_map(fn($h) => strtolower(trim($h)), array_shift($rows));
        $crafts = Craft::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id]);

        DB::transaction(function () use ($rows, $header, $crafts, &$result) {
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2; // +1 for header, +1 for 1-based indexing
                $data = $this->combineRow($header, $row);

                if (empty($data['employee_number'])) {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing employee_number.'];
                    continue;
                }

                try {
                    $payload = $this->buildEmployeePayload($data, $crafts);
                    $existing = Employee::where('employee_number', $data['employee_number'])->first();

                    if ($existing) {
                        $existing->update($payload);
                        $result['updated']++;
                    } else {
                        Employee::create($payload);
                        $result['created']++;
                    }
                } catch (\Throwable $e) {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => $e->getMessage()];
                }
            }
        });

        return back()->with('import_result', $result);
    }

    private function buildEmployeePayload(array $data, $craftsByName): array
    {
        $craftId = null;
        if (!empty($data['craft_name'])) {
            $key = strtolower(trim($data['craft_name']));
            $craftId = $craftsByName[$key] ?? null;
        }

        return [
            'employee_number'     => $data['employee_number'],
            'legacy_employee_id'  => $data['legacy_employee_id'] ?? null,
            'legacy_position'     => $data['legacy_position'] ?? null,
            'legacy_craft'        => $data['legacy_craft'] ?? null,
            'first_name'          => $data['first_name'] ?? '',
            'middle_name'         => $data['middle_name'] ?? null,
            'last_name'           => $data['last_name'] ?? '',
            'email'               => $this->blankToNull($data['email'] ?? null),
            'phone'               => $this->blankToNull($data['phone'] ?? null),
            'address_1'           => $this->blankToNull($data['address_1'] ?? null),
            'address_2'           => $this->blankToNull($data['address_2'] ?? null),
            'city'                => $this->blankToNull($data['city'] ?? null),
            'state'               => $this->blankToNull($data['state'] ?? null),
            'zip'                 => $this->blankToNull($data['zip'] ?? null),
            'home_phone'          => $this->blankToNull($data['home_phone'] ?? null),
            'work_cell'           => $this->blankToNull($data['work_cell'] ?? null),
            'personal_cell'       => $this->blankToNull($data['personal_cell'] ?? null),
            'craft_id'            => $craftId,
            'role'                => $data['role'] ?? 'field',
            'employee_type'       => $this->blankToNull($data['employee_type'] ?? null),
            'department'          => $this->blankToNull($data['department'] ?? null),
            'classification'      => $this->blankToNull($data['classification'] ?? null),
            'union'               => $this->blankToNull($data['union'] ?? null),
            'is_supervisor'       => $this->truthy($data['is_supervisor'] ?? false),
            'certified_pay'       => $this->truthy($data['certified_pay'] ?? false),
            'pay_cycle'           => $this->blankToNull($data['pay_cycle'] ?? null) ?? 'weekly',
            'pay_type'            => $this->blankToNull($data['pay_type'] ?? null) ?? 'hourly',
            'hourly_rate'         => $this->toDecimal($data['hourly_rate'] ?? 0),
            'overtime_rate'       => $this->toDecimal($data['overtime_rate'] ?? 0),
            'billable_rate'       => $this->toDecimal($data['billable_rate'] ?? 0),
            'burden_rate'         => $this->toDecimal($data['burden_rate'] ?? 0),
            'work_comp_code'      => $this->blankToNull($data['work_comp_code'] ?? null),
            'suta_state'          => $this->blankToNull($data['suta_state'] ?? null),
            'state_tax'           => $this->blankToNull($data['state_tax'] ?? null),
            'city_tax'            => $this->blankToNull($data['city_tax'] ?? null),
            'hire_date'           => $this->parseDate($data['hire_date'] ?? null) ?? now()->toDateString(),
            'start_date'          => $this->parseDate($data['start_date'] ?? null),
            'rehire_date'         => $this->parseDate($data['rehire_date'] ?? null),
            'term_date'           => $this->parseDate($data['term_date'] ?? null),
            'term_reason'         => $this->blankToNull($data['term_reason'] ?? null),
            'status'              => $this->blankToNull($data['status'] ?? null) ?? 'active',
        ];
    }

    // ─── Project Billable Rates (Billable Crafts) ─────────────────────────

    private const BILLABLE_RATE_COLUMNS = [
        'craft_name', 'employee_number', 'base_hourly_rate',
        'payroll_tax_rate', 'burden_rate', 'insurance_rate',
        'job_expenses_rate', 'consumables_rate', 'overhead_rate', 'profit_rate',
        'effective_date', 'notes',
    ];

    public function billableRateTemplate(Project $project): StreamedResponse
    {
        return $this->streamCsv(
            "project_{$project->id}_billable_rates_template.csv",
            self::BILLABLE_RATE_COLUMNS,
            [
                ['Crane Operator', '', '32.50', '0.0765', '0.45', '0.0325', '0.05', '0.02', '0.12', '0.10', '2026-01-01', 'Base crane op rate'],
                ['Laborer', '', '22.00', '0.0765', '0.35', '0.0325', '0.05', '0.02', '0.12', '0.10', '2026-01-01', ''],
            ]
        );
    }

    public function billableRateImport(Request $request, Project $project): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->parseCsv($request->file('file')->getRealPath());
        if (empty($rows)) {
            return back()->with('import_result', $result + ['errors' => [['row' => 0, 'message' => 'CSV file is empty.']]]);
        }

        $header = array_map(fn($h) => strtolower(trim($h)), array_shift($rows));
        $craftsByName = Craft::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id]);
        $employeesByNumber = Employee::pluck('id', 'employee_number');

        DB::transaction(function () use ($rows, $header, $craftsByName, $employeesByNumber, $project, &$result) {
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2;
                $data = $this->combineRow($header, $row);

                try {
                    $craftId = null;
                    if (!empty($data['craft_name'])) {
                        $craftId = $craftsByName[strtolower(trim($data['craft_name']))] ?? null;
                        if (!$craftId) {
                            throw new \RuntimeException("Unknown craft: {$data['craft_name']}");
                        }
                    }
                    $employeeId = !empty($data['employee_number'])
                        ? ($employeesByNumber[$data['employee_number']] ?? null)
                        : null;

                    if (!$craftId && !$employeeId) {
                        throw new \RuntimeException('Row must have either craft_name or employee_number.');
                    }

                    $effectiveDate = $this->parseDate($data['effective_date'] ?? null) ?? now()->toDateString();

                    $attributes = [
                        'project_id' => $project->id,
                        'craft_id' => $craftId,
                        'employee_id' => $employeeId,
                        'effective_date' => $effectiveDate,
                    ];

                    $values = [
                        'base_hourly_rate'  => $this->toDecimal($data['base_hourly_rate'] ?? 0),
                        'payroll_tax_rate'  => $this->toDecimal($data['payroll_tax_rate'] ?? 0, 4),
                        'burden_rate'       => $this->toDecimal($data['burden_rate'] ?? 0, 4),
                        'insurance_rate'    => $this->toDecimal($data['insurance_rate'] ?? 0, 4),
                        'job_expenses_rate' => $this->toDecimal($data['job_expenses_rate'] ?? 0, 4),
                        'consumables_rate'  => $this->toDecimal($data['consumables_rate'] ?? 0, 4),
                        'overhead_rate'     => $this->toDecimal($data['overhead_rate'] ?? 0, 4),
                        'profit_rate'       => $this->toDecimal($data['profit_rate'] ?? 0, 4),
                        'notes'             => $this->blankToNull($data['notes'] ?? null),
                    ];

                    $existing = ProjectBillableRate::where($attributes)->first();
                    if ($existing) {
                        $existing->fill($values)->save();
                        $result['updated']++;
                    } else {
                        ProjectBillableRate::create($attributes + $values);
                        $result['created']++;
                    }
                } catch (\Throwable $e) {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => $e->getMessage()];
                }
            }
        });

        return back()->with('import_result', $result);
    }

    // ─── Estimate Lines ───────────────────────────────────────────────────

    private const ESTIMATE_LINE_COLUMNS = [
        'cost_code', 'description', 'quantity', 'unit', 'unit_cost', 'labor_hours',
    ];

    public function estimateLineTemplate(Project $project, Estimate $estimate): StreamedResponse
    {
        return $this->streamCsv(
            "estimate_{$estimate->id}_lines_template.csv",
            self::ESTIMATE_LINE_COLUMNS,
            [
                ['01-100', 'Site preparation labor', '40', 'HRS', '32.50', '40'],
                ['02-200', 'Concrete (3000 PSI)', '125', 'CY', '185.00', '0'],
                ['04-100', 'Crane rental', '5', 'DAY', '1200.00', '0'],
            ]
        );
    }

    public function estimateLineImport(Request $request, Project $project, Estimate $estimate): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->parseCsv($request->file('file')->getRealPath());
        if (empty($rows)) {
            return back()->with('import_result', $result + ['errors' => [['row' => 0, 'message' => 'CSV file is empty.']]]);
        }

        $header = array_map(fn($h) => strtolower(trim($h)), array_shift($rows));
        $costCodesByCode = CostCode::pluck('id', 'code');

        DB::transaction(function () use ($rows, $header, $costCodesByCode, $estimate, &$result) {
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2;
                $data = $this->combineRow($header, $row);

                try {
                    $costCodeId = null;
                    if (!empty($data['cost_code'])) {
                        $costCodeId = $costCodesByCode[$data['cost_code']] ?? null;
                        if (!$costCodeId) {
                            throw new \RuntimeException("Unknown cost code: {$data['cost_code']}");
                        }
                    }

                    $quantity = $this->toDecimal($data['quantity'] ?? 0);
                    $unitCost = $this->toDecimal($data['unit_cost'] ?? 0);

                    EstimateLine::create([
                        'estimate_id' => $estimate->id,
                        'cost_code_id' => $costCodeId,
                        'description' => $this->blankToNull($data['description'] ?? null),
                        'quantity' => $quantity,
                        'unit' => $this->blankToNull($data['unit'] ?? null),
                        'unit_cost' => $unitCost,
                        'amount' => round($quantity * $unitCost, 2),
                        'labor_hours' => $this->toDecimal($data['labor_hours'] ?? 0),
                    ]);
                    $result['created']++;
                } catch (\Throwable $e) {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => $e->getMessage()];
                }
            }
        });

        return back()->with('import_result', $result);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /** Stream a CSV file to the browser as a download. */
    private function streamCsv(string $filename, array $header, array $rows): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens it correctly
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, $headers);
    }

    /** Read a CSV file into an array of rows. */
    private function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) === false) {
            return [];
        }
        while (($data = fgetcsv($handle)) !== false) {
            // Strip BOM from first cell of first row
            if (empty($rows) && isset($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
            }
            // Skip completely-empty rows
            if (count(array_filter($data, fn($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }
            $rows[] = $data;
        }
        fclose($handle);
        return $rows;
    }

    /** Combine a header and row into an associative array. */
    private function combineRow(array $header, array $row): array
    {
        $row = array_pad($row, count($header), '');
        $row = array_slice($row, 0, count($header));
        return array_combine($header, array_map(fn($v) => is_string($v) ? trim($v) : $v, $row));
    }

    private function blankToNull($value): ?string
    {
        if ($value === null) return null;
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function toDecimal($value, int $decimals = 2): float
    {
        if ($value === null || $value === '') return 0.0;
        // Strip $ , and spaces
        $clean = preg_replace('/[\$,\s]/', '', (string) $value);
        return round((float) $clean, $decimals);
    }

    private function truthy($value): bool
    {
        if (is_bool($value)) return $value;
        $v = strtolower(trim((string) $value));
        return in_array($v, ['1', 'true', 'yes', 'y', 't'], true);
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) return null;
        $value = trim((string) $value);
        // Reject placeholders like "00/00/0000"
        if (preg_match('/^0{1,4}[-\/]0{1,2}[-\/]0{1,4}$/', $value)) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            throw new \RuntimeException("Invalid date: {$value}");
        }
    }
}
