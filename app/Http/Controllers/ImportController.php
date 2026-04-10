<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CostCode;
use App\Models\Craft;
use App\Models\Employee;
use App\Models\Estimate;
use App\Models\EstimateLine;
use App\Models\Project;
use App\Models\ProjectBillableRate;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->parseFile($request->file('file'));
        if (empty($rows)) {
            return back()->with('import_result', ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [['row' => 0, 'message' => 'File is empty or could not be read.']]]);
        }

        $header = $this->normalizeHeader(array_shift($rows));
        $crafts = Craft::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id]);

        // Determine next auto-number if employee_number column is absent/empty
        $nextAutoNum = (int) Employee::max('id') + 1;

        DB::transaction(function () use ($rows, $header, $crafts, &$result, &$nextAutoNum) {
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2;
                $data = $this->combineRow($header, $row);

                // Must have at least a first_name or last_name
                $firstName = trim($data['first_name'] ?? '');
                $lastName  = trim($data['last_name'] ?? '');
                if ($firstName === '' && $lastName === '') {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing name (first_name or last_name).'];
                    continue;
                }

                // Auto-generate employee_number if missing
                if (empty($data['employee_number'])) {
                    $data['employee_number'] = 'EMP-' . str_pad($nextAutoNum, 4, '0', STR_PAD_LEFT);
                    $nextAutoNum++;
                }

                try {
                    $payload = $this->buildEmployeePayload($data, $crafts);
                    $existing = Employee::where('employee_number', $data['employee_number'])->first();

                    // Also try matching by exact name if employee_number was auto-generated
                    if (!$existing && $firstName !== '' && $lastName !== '') {
                        $existing = Employee::where('first_name', $firstName)
                            ->where('last_name', $lastName)
                            ->first();
                    }

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

        // Handle "Full Name" in first_name: split into first/last if last_name is empty
        $firstName = $data['first_name'] ?? '';
        $lastName  = $data['last_name'] ?? '';
        if ($firstName !== '' && $lastName === '' && str_contains($firstName, ' ')) {
            $parts = explode(' ', $firstName, 2);
            $firstName = $parts[0];
            $lastName  = $parts[1] ?? '';
        }
        // Handle "Last, First" format
        if ($firstName !== '' && $lastName === '' && str_contains($firstName, ',')) {
            $parts = array_map('trim', explode(',', $firstName, 2));
            $lastName  = $parts[0];
            $firstName = $parts[1] ?? '';
        }

        return [
            'employee_number'     => $data['employee_number'],
            'legacy_employee_id'  => $data['legacy_employee_id'] ?? null,
            'legacy_position'     => $data['legacy_position'] ?? null,
            'legacy_craft'        => $data['legacy_craft'] ?? null,
            'first_name'          => $firstName,
            'middle_name'         => $data['middle_name'] ?? null,
            'last_name'           => $lastName,
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
            'role'                => $this->normalizeEnum($data['role'] ?? null, ['field', 'foreman', 'superintendent', 'project_manager', 'admin', 'accounting'], 'field'),
            'employee_type'       => $this->blankToNull($data['employee_type'] ?? null),
            'department'          => $this->blankToNull($data['department'] ?? null),
            'classification'      => $this->blankToNull($data['classification'] ?? null),
            'union'               => $this->blankToNull($data['union'] ?? null),
            'is_supervisor'       => $this->truthy($data['is_supervisor'] ?? false),
            'certified_pay'       => $this->truthy($data['certified_pay'] ?? false),
            'pay_cycle'           => $this->normalizeEnum($data['pay_cycle'] ?? null, ['weekly', 'bi_weekly', 'semi_monthly', 'monthly'], 'weekly'),
            'pay_type'            => $this->normalizeEnum($data['pay_type'] ?? null, ['hourly', 'salary'], 'hourly'),
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
            'status'              => $this->normalizeEnum($data['status'] ?? null, ['active', 'inactive', 'terminated'], 'active'),
        ];
    }

    // ─── Crafts ───────────────────────────────────────────────────────────

    private const CRAFT_COLUMNS = [
        'code', 'name', 'description',
        'base_hourly_rate', 'overtime_multiplier', 'billable_rate',
        'ot_billable_rate', 'wc_rate', 'fica_rate', 'suta_rate',
        'benefits_rate', 'overhead_rate', 'is_active',
    ];

    public function craftTemplate(): StreamedResponse
    {
        return $this->streamCsv(
            'crafts_import_template.csv',
            self::CRAFT_COLUMNS,
            [
                ['CRANE-OP', 'Crane Operator', 'Licensed crane operator', '32.50', '1.5', '78.00', '48.75', '0.906', '0.0765', '0.0181', '3.71', '0.10', 'yes'],
                ['LAB-01',   'General Laborer', '',                        '22.00', '1.5', '55.00', '33.00', '0.906', '0.0765', '0.0181', '3.71', '0.10', 'yes'],
            ]
        );
    }

    public function craftImport(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->parseFile($request->file('file'));
        if (empty($rows)) {
            return back()->with('import_result', $result + ['errors' => [['row' => 0, 'message' => 'CSV file is empty.']]]);
        }

        $header = $this->normalizeHeader(array_shift($rows));

        DB::transaction(function () use ($rows, $header, &$result) {
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2;
                $data = $this->combineRow($header, $row);

                if (empty($data['code']) || empty($data['name'])) {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing required code or name.'];
                    continue;
                }

                try {
                    $payload = [
                        'name'                => $data['name'],
                        'description'         => $this->blankToNull($data['description'] ?? null),
                        'base_hourly_rate'    => $this->toDecimal($data['base_hourly_rate'] ?? 0),
                        'overtime_multiplier' => $this->toDecimal($data['overtime_multiplier'] ?? 1.5),
                        'billable_rate'       => $this->toDecimal($data['billable_rate'] ?? 0),
                        'ot_billable_rate'    => $this->nullableDecimal($data['ot_billable_rate'] ?? null),
                        'wc_rate'             => $this->nullableDecimal($data['wc_rate'] ?? null, 4),
                        'fica_rate'           => $this->nullableDecimal($data['fica_rate'] ?? null, 4),
                        'suta_rate'           => $this->nullableDecimal($data['suta_rate'] ?? null, 4),
                        'benefits_rate'       => $this->nullableDecimal($data['benefits_rate'] ?? null),
                        'overhead_rate'       => $this->nullableDecimal($data['overhead_rate'] ?? null, 4),
                        'is_active'           => $this->truthy($data['is_active'] ?? true),
                    ];

                    $existing = Craft::where('code', $data['code'])->first();
                    if ($existing) {
                        $existing->update($payload);
                        $result['updated']++;
                    } else {
                        Craft::create($payload + ['code' => $data['code']]);
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
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->parseFile($request->file('file'));
        if (empty($rows)) {
            return back()->with('import_result', $result + ['errors' => [['row' => 0, 'message' => 'CSV file is empty.']]]);
        }

        $header = $this->normalizeHeader(array_shift($rows));
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
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->parseFile($request->file('file'));
        if (empty($rows)) {
            return back()->with('import_result', $result + ['errors' => [['row' => 0, 'message' => 'CSV file is empty.']]]);
        }

        $header = $this->normalizeHeader(array_shift($rows));
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

    // ─── Vendors ──────────────────────────────────────────────────────────

    private const VENDOR_COLUMNS = [
        'vendor_code', 'name', 'contact_name', 'email', 'phone',
        'address', 'city', 'state', 'zip',
        'type', 'specialty', 'is_preferred', 'is_active',
    ];

    public function vendorTemplate(): StreamedResponse
    {
        return $this->streamCsv(
            'vendors_import_template.csv',
            self::VENDOR_COLUMNS,
            [
                ['V-1001', 'Acme Steel Supply', 'John Smith', 'john@acmesteel.com', '(251) 555-0101',
                 '100 Industrial Blvd', 'Mobile', 'AL', '36602',
                 'supplier', 'Structural steel', 'yes', 'yes'],
                ['V-1002', 'Gulf Coast Concrete', 'Mary Johnson', 'mary@gccconcrete.com', '(251) 555-0202',
                 '250 Port Rd', 'Mobile', 'AL', '36603',
                 'supplier', 'Ready-mix concrete', 'no', 'yes'],
                ['V-2001', 'Delta Electrical Subs', 'Bob Wilson', 'bob@deltaelec.com', '(504) 555-0303',
                 '789 Canal St', 'New Orleans', 'LA', '70130',
                 'subcontractor', 'Electrical', 'yes', 'yes'],
            ]
        );
    }

    public function vendorImport(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->parseFile($request->file('file'));
        if (empty($rows)) {
            return back()->with('import_result', $result + ['errors' => [['row' => 0, 'message' => 'CSV file is empty.']]]);
        }

        $header = $this->normalizeHeader(array_shift($rows));
        $allowedTypes = ['subcontractor', 'supplier', 'rental', 'other'];

        DB::transaction(function () use ($rows, $header, $allowedTypes, &$result) {
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2;
                $data = $this->combineRow($header, $row);

                if (empty($data['name'])) {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing required name.'];
                    continue;
                }

                try {
                    $type = strtolower(trim($data['type'] ?? 'supplier'));
                    if (!in_array($type, $allowedTypes, true)) {
                        $type = 'supplier';
                    }

                    $payload = [
                        'vendor_code'  => $this->blankToNull($data['vendor_code'] ?? null),
                        'name'         => $data['name'],
                        'contact_name' => $this->blankToNull($data['contact_name'] ?? null),
                        'email'        => $this->blankToNull($data['email'] ?? null),
                        'phone'        => $this->blankToNull($data['phone'] ?? null),
                        'address'      => $this->blankToNull($data['address'] ?? null),
                        'city'         => $this->blankToNull($data['city'] ?? null),
                        'state'        => $this->blankToNull($data['state'] ?? null),
                        'zip'          => $this->blankToNull($data['zip'] ?? null),
                        'type'         => $type,
                        'specialty'    => $this->blankToNull($data['specialty'] ?? null),
                        'is_preferred' => $this->truthy($data['is_preferred'] ?? false),
                        'is_active'    => $this->truthy($data['is_active'] ?? true),
                    ];

                    // Match on vendor_code first (legacy identifier), fall back to exact name match.
                    $existing = null;
                    if (!empty($payload['vendor_code'])) {
                        $existing = Vendor::where('vendor_code', $payload['vendor_code'])->first();
                    }
                    if (!$existing) {
                        $existing = Vendor::where('name', $payload['name'])->first();
                    }

                    if ($existing) {
                        $existing->update($payload);
                        $result['updated']++;
                    } else {
                        Vendor::create($payload);
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

    // ─── Clients ──────────────────────────────────────────────────────────

    private const CLIENT_COLUMNS = [
        'vendor_code', 'name', 'contact_name', 'email', 'phone',
        'address', 'city', 'state', 'zip',
    ];

    public function clientTemplate(): StreamedResponse
    {
        return $this->streamCsv(
            'clients_import_template.csv',
            self::CLIENT_COLUMNS,
            [
                ['C-1001', 'Port Authority of Mobile', 'Sarah Lee', 'sarah.lee@portmobile.gov', '(251) 555-0401',
                 '250 Water St', 'Mobile', 'AL', '36602'],
                ['C-1002', 'Bengal Refining LLC', 'Mike Davis', 'mdavis@bengalref.com', '(225) 555-0502',
                 '500 River Rd', 'Baton Rouge', 'LA', '70802'],
            ]
        );
    }

    public function clientImport(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->parseFile($request->file('file'));
        if (empty($rows)) {
            return back()->with('import_result', $result + ['errors' => [['row' => 0, 'message' => 'CSV file is empty.']]]);
        }

        $header = $this->normalizeHeader(array_shift($rows));

        DB::transaction(function () use ($rows, $header, &$result) {
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2;
                $data = $this->combineRow($header, $row);

                if (empty($data['name'])) {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing required name.'];
                    continue;
                }

                try {
                    $payload = [
                        'vendor_code'  => $this->blankToNull($data['vendor_code'] ?? null),
                        'name'         => $data['name'],
                        'contact_name' => $this->blankToNull($data['contact_name'] ?? null),
                        'email'        => $this->blankToNull($data['email'] ?? null),
                        'phone'        => $this->blankToNull($data['phone'] ?? null),
                        'address'      => $this->blankToNull($data['address'] ?? null),
                        'city'         => $this->blankToNull($data['city'] ?? null),
                        'state'        => $this->blankToNull($data['state'] ?? null),
                        'zip'          => $this->blankToNull($data['zip'] ?? null),
                    ];

                    // Match on vendor_code first (legacy identifier), fall back to exact name match.
                    $existing = null;
                    if (!empty($payload['vendor_code'])) {
                        $existing = Client::where('vendor_code', $payload['vendor_code'])->first();
                    }
                    if (!$existing) {
                        $existing = Client::where('name', $payload['name'])->first();
                    }

                    if ($existing) {
                        $existing->update($payload);
                        $result['updated']++;
                    } else {
                        Client::create($payload);
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

    // ─── Cost Codes ──────────────────────────────────────────────────────

    private const COST_CODE_COLUMNS = [
        'code', 'name', 'category', 'cost_type', 'parent_code', 'description', 'sort_order', 'is_active',
    ];

    public function costCodeTemplate(): StreamedResponse
    {
        return $this->streamCsv(
            'cost_codes_import_template.csv',
            self::COST_CODE_COLUMNS,
            [
                ['01',        'General Conditions', 'labor', '',              '',   'General conditions phase',  '1', 'yes'],
                ['01.10.000', 'T & M Labor',        'labor', 'Direct Labor', '01', 'T & M Direct Labor',        '2', 'yes'],
                ['02',        'Sitework',            'labor', '',              '',   'Sitework phase',            '3', 'yes'],
                ['03',        'Materials',           'material', '',           '',   'Materials & supplies',      '4', 'yes'],
            ]
        );
    }

    public function costCodeImport(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->parseFile($request->file('file'));
        if (empty($rows)) {
            return back()->with('import_result', $result + ['errors' => [['row' => 0, 'message' => 'File is empty.']]]);
        }

        $header = $this->normalizeHeader(array_shift($rows));

        // First pass: create/update all codes so parent references resolve
        DB::transaction(function () use ($rows, $header, &$result) {
            // Pass 1: create/update all cost codes
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2;
                $data = $this->combineRow($header, $row);

                if (empty($data['code']) || empty($data['name'])) {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing required code or name.'];
                    continue;
                }

                try {
                    $payload = [
                        'name'        => $data['name'],
                        'category'    => $this->blankToNull($data['category'] ?? null),
                        'cost_type'   => $this->blankToNull($data['cost_type'] ?? null),
                        'description' => $this->blankToNull($data['description'] ?? null),
                        'sort_order'  => (int) ($data['sort_order'] ?? 0),
                        'is_active'   => $this->truthy($data['is_active'] ?? true),
                    ];

                    $existing = CostCode::where('code', $data['code'])->first();
                    if ($existing) {
                        $existing->update($payload);
                        $result['updated']++;
                    } else {
                        CostCode::create($payload + ['code' => $data['code']]);
                        $result['created']++;
                    }
                } catch (\Throwable $e) {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => $e->getMessage()];
                }
            }

            // Pass 2: resolve parent_code references
            foreach ($rows as $row) {
                $data = $this->combineRow($header, $row);
                $parentCode = $this->blankToNull($data['parent_code'] ?? null);
                if ($parentCode && !empty($data['code'])) {
                    $parent = CostCode::where('code', $parentCode)->first();
                    $child = CostCode::where('code', $data['code'])->first();
                    if ($parent && $child && $child->parent_id !== $parent->id) {
                        $child->update(['parent_id' => $parent->id]);
                    }
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

    /**
     * Read a CSV or Excel file into an array of rows.
     * First row is the header. Supports .csv, .txt, .xlsx, .xls.
     */
    private function parseFile(\Illuminate\Http\UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['xlsx', 'xls'])) {
            return $this->parseExcel($file->getRealPath());
        }

        return $this->parseCsv($file->getRealPath());
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) === false) {
            return [];
        }
        while (($data = fgetcsv($handle)) !== false) {
            if (empty($rows) && isset($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
            }
            if (count(array_filter($data, fn($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }
            $rows[] = $data;
        }
        fclose($handle);
        return $rows;
    }

    /** Read an Excel file into an array of rows using PhpSpreadsheet. */
    private function parseExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $val = $cell->getValue();
                $rowData[] = $val !== null ? (string) $val : '';
            }
            if (count(array_filter($rowData, fn($v) => $v !== '')) > 0) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    /**
     * Normalize header column names so common variations all map to our
     * canonical snake_case names. E.g. "First Name" → "first_name",
     * "Employee ID" → "employee_number", "Emp #" → "employee_number".
     */
    private function normalizeHeader(array $rawHeader): array
    {
        $aliases = [
            // Employee variants
            'employee id'       => 'employee_number',
            'employee_id'       => 'employee_number',
            'emp #'             => 'employee_number',
            'emp_#'             => 'employee_number',
            'emp id'            => 'employee_number',
            'emp_id'            => 'employee_number',
            'emp no'            => 'employee_number',
            'number'            => 'employee_number',
            'id'                => 'employee_number',
            'first name'        => 'first_name',
            'firstname'         => 'first_name',
            'first'             => 'first_name',
            'last name'         => 'last_name',
            'lastname'          => 'last_name',
            'last'              => 'last_name',
            'middle name'       => 'middle_name',
            'middlename'        => 'middle_name',
            'middle'            => 'middle_name',
            'name'              => 'first_name',
            'full name'         => 'first_name',
            'full_name'         => 'first_name',

            // Contact
            'e-mail'            => 'email',
            'email address'     => 'email',
            'phone number'      => 'phone',
            'telephone'         => 'phone',
            'cell'              => 'personal_cell',
            'cell phone'        => 'personal_cell',
            'mobile'            => 'personal_cell',
            'mobile phone'      => 'personal_cell',
            'home phone'        => 'home_phone',
            'work cell'         => 'work_cell',
            'work phone'        => 'work_cell',
            'personal cell'     => 'personal_cell',

            // Address
            'address'           => 'address_1',
            'address 1'         => 'address_1',
            'street'            => 'address_1',
            'street address'    => 'address_1',
            'address 2'         => 'address_2',
            'zip code'          => 'zip',
            'zipcode'           => 'zip',
            'postal code'       => 'zip',
            'postal_code'       => 'zip',

            // Work
            'craft'             => 'craft_name',
            'trade'             => 'craft_name',
            'classification'    => 'craft_name',
            'position'          => 'legacy_position',
            'title'             => 'employee_type',
            'job title'         => 'employee_type',
            'dept'              => 'department',

            // Rates
            'hourly rate'       => 'hourly_rate',
            'rate'              => 'hourly_rate',
            'pay rate'          => 'hourly_rate',
            'ot rate'           => 'overtime_rate',
            'overtime rate'     => 'overtime_rate',
            'bill rate'         => 'billable_rate',
            'billable rate'     => 'billable_rate',

            // Dates
            'hire date'         => 'hire_date',
            'hired'             => 'hire_date',
            'date hired'        => 'hire_date',
            'start date'        => 'start_date',
            'started'           => 'start_date',
            'term date'         => 'term_date',
            'termination date'  => 'term_date',
            'terminated'        => 'term_date',
            'rehire date'       => 'rehire_date',

            // Vendor / Client
            'vendor code'       => 'vendor_code',
            'legacy code'       => 'vendor_code',
            'legacy id'         => 'vendor_code',
            'company'           => 'name',
            'company name'      => 'name',
            'vendor name'       => 'name',
            'client name'       => 'name',
            'contact'           => 'contact_name',
            'contact name'      => 'contact_name',
            'contact person'    => 'contact_name',
            'type'              => 'type',
            'vendor type'       => 'type',

            // Craft
            'code'              => 'code',
            'craft code'        => 'code',
            'craftcode'         => 'code',
            'craft name'        => 'name',
            'description'       => 'description',
            'hourly'            => 'base_hourly_rate',
            'base hourly rate'  => 'base_hourly_rate',
            'base rate'         => 'base_hourly_rate',
            'st base wage'      => 'base_hourly_rate',
            'payrate'           => 'base_hourly_rate',
            'pay rate'          => 'base_hourly_rate',
            'ot multiplier'     => 'overtime_multiplier',
            'overtime multiplier' => 'overtime_multiplier',
            'multiplier'        => 'overtime_multiplier',
            'ot billable rate'  => 'ot_billable_rate',
            'overtime billable rate' => 'ot_billable_rate',
            'ot base wage'      => 'ot_billable_rate',
            'payrateot'         => 'ot_billable_rate',
            'wc'                => 'wc_rate',
            'wc rate'           => 'wc_rate',
            'wc (hr)'           => 'wc_rate',
            'workers comp'      => 'wc_rate',
            'fica'              => 'fica_rate',
            'fica%'             => 'fica_rate',
            'fica rate'         => 'fica_rate',
            'suta'              => 'suta_rate',
            'suta%'             => 'suta_rate',
            'suta rate'         => 'suta_rate',
            'benefits'          => 'benefits_rate',
            'benefits rate'     => 'benefits_rate',
            'benefits st($/hr)' => 'benefits_rate',
            'employee benefits st ($/hr)' => 'benefits_rate',
            'overhead'          => 'overhead_rate',
            'overhead rate'     => 'overhead_rate',
            'overhead burden'   => 'overhead_rate',
            'active'            => 'is_active',
            'preferred'         => 'is_preferred',

            // Cost Codes
            'phase code'        => 'code',
            'cost code'         => 'code',
            'cost type'         => 'cost_type',
            'category'          => 'category',
            'parent'            => 'parent_code',
            'parent code'       => 'parent_code',
            'sort order'        => 'sort_order',
            'sort'              => 'sort_order',
        ];

        return array_map(function ($h) use ($aliases) {
            $normalized = strtolower(trim((string) $h));
            // Direct match on alias
            if (isset($aliases[$normalized])) {
                return $aliases[$normalized];
            }
            // Already snake_case canonical
            return preg_replace('/[\s\-]+/', '_', $normalized);
        }, $rawHeader);
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

    private function nullableDecimal($value, int $decimals = 2): ?float
    {
        if ($value === null || trim((string) $value) === '') return null;
        $clean = preg_replace('/[\$,\s]/', '', (string) $value);
        $num = (float) $clean;
        return $num == 0 ? null : round($num, $decimals);
    }

    /** Normalize a value to match a DB enum: lowercase, replace hyphens/spaces with underscores, fallback to default. */
    private function normalizeEnum($value, array $allowed, string $default): string
    {
        if ($value === null || trim((string) $value) === '') return $default;
        $normalized = strtolower(trim((string) $value));
        $normalized = str_replace(['-', ' '], '_', $normalized);
        return in_array($normalized, $allowed, true) ? $normalized : $default;
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
