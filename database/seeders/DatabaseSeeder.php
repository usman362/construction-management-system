<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Craft;
use App\Models\Shift;
use App\Models\Client;
use App\Models\Employee;
use App\Models\CostCode;
use App\Models\Vendor;
use App\Models\Project;
use App\Models\BudgetLine;
use App\Models\Commitment;
use App\Models\Invoice;
use App\Models\ChangeOrder;
use App\Models\ChangeOrderLabor;
use App\Models\ChangeOrderItem;
use App\Models\Crew;
use App\Models\CrewMember;
use App\Models\Timesheet;
use App\Models\ManhourBudget;
use App\Models\Estimate;
use App\Models\EstimateLine;
use App\Models\PerDiemRate;
use App\Models\PayrollPeriod;
use App\Models\DailyLog;
use App\Models\Equipment;
use App\Models\EquipmentAssignment;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\BillingInvoice;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =============================================
        // All calls use firstOrCreate / updateOrCreate
        // so the seeder is safe to run multiple times.
        // =============================================

        // 1. ADMIN USER
        $admin = User::firstOrCreate(
            ['email' => 'admin@cms.com'],
            ['name' => 'Admin User', 'password' => Hash::make('password')]
        );

        // 2. CRAFTS
        $crafts = [];
        $craftData = [
            ['code' => 'PF', 'name' => 'Pipe Fitter', 'base_hourly_rate' => 62.62, 'overtime_multiplier' => 1.5, 'billable_rate' => 85.00],
            ['code' => 'QA', 'name' => 'QA/QC', 'base_hourly_rate' => 74.55, 'overtime_multiplier' => 1.5, 'billable_rate' => 95.00],
            ['code' => 'WE', 'name' => 'Welder', 'base_hourly_rate' => 65.00, 'overtime_multiplier' => 1.5, 'billable_rate' => 88.00],
            ['code' => 'HP', 'name' => 'Helper', 'base_hourly_rate' => 35.00, 'overtime_multiplier' => 1.5, 'billable_rate' => 50.00],
            ['code' => 'FO', 'name' => 'Foreman', 'base_hourly_rate' => 72.00, 'overtime_multiplier' => 1.5, 'billable_rate' => 95.00],
            ['code' => 'SU', 'name' => 'Superintendent', 'base_hourly_rate' => 80.00, 'overtime_multiplier' => 1.5, 'billable_rate' => 110.00],
            ['code' => 'EL', 'name' => 'Electrician', 'base_hourly_rate' => 60.00, 'overtime_multiplier' => 1.5, 'billable_rate' => 82.00],
            ['code' => 'IG', 'name' => 'Ironworker/General', 'base_hourly_rate' => 55.00, 'overtime_multiplier' => 1.5, 'billable_rate' => 75.00],
        ];
        foreach ($craftData as $c) {
            $code = $c['code'];
            $crafts[$code] = Craft::firstOrCreate(['code' => $code], $c);
        }

        // 3. SHIFTS
        $dayShift = Shift::firstOrCreate(
            ['name' => 'Day Shift'],
            ['start_time' => '06:00', 'end_time' => '16:00', 'hours_per_day' => 10, 'multiplier' => 1.0]
        );
        $nightShift = Shift::firstOrCreate(
            ['name' => 'Night Shift'],
            ['start_time' => '18:00', 'end_time' => '04:00', 'hours_per_day' => 10, 'multiplier' => 1.15]
        );
        $weekendShift = Shift::firstOrCreate(
            ['name' => 'Weekend'],
            ['start_time' => '06:00', 'end_time' => '16:00', 'hours_per_day' => 10, 'multiplier' => 1.5]
        );

        // 4. CLIENTS
        $client1 = Client::firstOrCreate(
            ['name' => 'Industrial Partners LLC'],
            ['contact_name' => 'John Smith', 'email' => 'jsmith@industrialpartners.com', 'phone' => '225-555-0100', 'address' => '1111 East Airline Hwy', 'city' => 'Gramercy', 'state' => 'LA', 'zip' => '70752']
        );
        $client2 = Client::firstOrCreate(
            ['name' => 'Gulf Coast Refinery Corp'],
            ['contact_name' => 'Sarah Johnson', 'email' => 'sjohnson@gulfcoast.com', 'phone' => '504-555-0200', 'address' => '4500 River Road', 'city' => 'Baton Rouge', 'state' => 'LA', 'zip' => '70801']
        );

        // 5. EMPLOYEES
        $employees = [];
        $empData = [
            ['employee_number' => 'E001', 'first_name' => 'Mike', 'last_name' => 'Thompson', 'role' => 'superintendent', 'craft_code' => 'SU', 'hourly_rate' => 80.00, 'overtime_rate' => 120.00, 'billable_rate' => 110.00],
            ['employee_number' => 'E002', 'first_name' => 'James', 'last_name' => 'Wilson', 'role' => 'foreman', 'craft_code' => 'FO', 'hourly_rate' => 72.00, 'overtime_rate' => 108.00, 'billable_rate' => 95.00],
            ['employee_number' => 'E003', 'first_name' => 'Robert', 'last_name' => 'Garcia', 'role' => 'field', 'craft_code' => 'PF', 'hourly_rate' => 62.62, 'overtime_rate' => 93.93, 'billable_rate' => 85.00],
            ['employee_number' => 'E004', 'first_name' => 'David', 'last_name' => 'Martinez', 'role' => 'field', 'craft_code' => 'PF', 'hourly_rate' => 62.62, 'overtime_rate' => 93.93, 'billable_rate' => 85.00],
            ['employee_number' => 'E005', 'first_name' => 'Chris', 'last_name' => 'Brown', 'role' => 'field', 'craft_code' => 'WE', 'hourly_rate' => 65.00, 'overtime_rate' => 97.50, 'billable_rate' => 88.00],
            ['employee_number' => 'E006', 'first_name' => 'Jason', 'last_name' => 'Lee', 'role' => 'field', 'craft_code' => 'QA', 'hourly_rate' => 74.55, 'overtime_rate' => 111.83, 'billable_rate' => 95.00],
            ['employee_number' => 'E007', 'first_name' => 'Kevin', 'last_name' => 'Davis', 'role' => 'field', 'craft_code' => 'HP', 'hourly_rate' => 35.00, 'overtime_rate' => 52.50, 'billable_rate' => 50.00],
            ['employee_number' => 'E008', 'first_name' => 'Mark', 'last_name' => 'Taylor', 'role' => 'field', 'craft_code' => 'EL', 'hourly_rate' => 60.00, 'overtime_rate' => 90.00, 'billable_rate' => 82.00],
            ['employee_number' => 'E009', 'first_name' => 'Steven', 'last_name' => 'Anderson', 'role' => 'field', 'craft_code' => 'PF', 'hourly_rate' => 62.62, 'overtime_rate' => 93.93, 'billable_rate' => 85.00],
            ['employee_number' => 'E010', 'first_name' => 'Brian', 'last_name' => 'Thomas', 'role' => 'project_manager', 'craft_code' => null, 'hourly_rate' => 85.00, 'overtime_rate' => 127.50, 'billable_rate' => 120.00],
            ['employee_number' => 'E011', 'first_name' => 'Daniel', 'last_name' => 'White', 'role' => 'field', 'craft_code' => 'IG', 'hourly_rate' => 55.00, 'overtime_rate' => 82.50, 'billable_rate' => 75.00],
            ['employee_number' => 'E012', 'first_name' => 'Paul', 'last_name' => 'Harris', 'role' => 'admin', 'craft_code' => null, 'hourly_rate' => 45.00, 'overtime_rate' => 67.50, 'billable_rate' => 65.00],
        ];
        foreach ($empData as $e) {
            $craftId = $e['craft_code'] ? $crafts[$e['craft_code']]->id : null;
            $empNum = $e['employee_number'];
            unset($e['craft_code']);
            $e['craft_id'] = $craftId;
            $e['hire_date'] = now()->subMonths(rand(6, 36));
            $e['status'] = 'active';
            $employees[$empNum] = Employee::firstOrCreate(
                ['employee_number' => $empNum],
                $e
            );
        }

        // 6. COST CODES (hierarchical, matching PDF)
        $labor = CostCode::firstOrCreate(['code' => 'LABOR'], ['name' => 'LABOR', 'sort_order' => 0]);
        $cc01  = CostCode::firstOrCreate(['code' => '01'],  ['name' => 'DIRECT LABOR', 'parent_id' => $labor->id, 'sort_order' => 1]);
        $cc010 = CostCode::firstOrCreate(['code' => '010'], ['name' => 'INDIRECT LABOR', 'parent_id' => $labor->id, 'sort_order' => 2]);

        $cc02 = CostCode::firstOrCreate(['code' => '02'], ['name' => 'MATERIAL', 'sort_order' => 3]);

        $cc03  = CostCode::firstOrCreate(['code' => '03'],  ['name' => '3RD PARTY RENTAL', 'sort_order' => 4]);
        $cc03a = CostCode::firstOrCreate(['code' => '03A'], ['name' => 'Welding Machine Rental', 'parent_id' => $cc03->id, 'sort_order' => 5]);
        $cc03b = CostCode::firstOrCreate(['code' => '03B'], ['name' => 'Generator', 'parent_id' => $cc03->id, 'sort_order' => 6]);
        $cc03c = CostCode::firstOrCreate(['code' => '03C'], ['name' => 'Fork Lift Rental', 'parent_id' => $cc03->id, 'sort_order' => 7]);
        $cc03d = CostCode::firstOrCreate(['code' => '03D'], ['name' => 'Restrooms', 'parent_id' => $cc03->id, 'sort_order' => 8]);
        $cc03e = CostCode::firstOrCreate(['code' => '03E'], ['name' => 'UTV', 'parent_id' => $cc03->id, 'sort_order' => 9]);
        $cc03f = CostCode::firstOrCreate(['code' => '03F'], ['name' => 'WEEKLY', 'parent_id' => $cc03->id, 'sort_order' => 10]);

        $cc04  = CostCode::firstOrCreate(['code' => '04'],  ['name' => 'COMPANY EQUIP', 'sort_order' => 11]);
        $cc04a = CostCode::firstOrCreate(['code' => '04A'], ['name' => 'Tool Trailer - WEEKLY', 'parent_id' => $cc04->id, 'sort_order' => 12]);

        $cc05 = CostCode::firstOrCreate(['code' => '05'], ['name' => 'CONSUMABLES TOOLS & SUPPLIES', 'sort_order' => 13]);

        $cc06  = CostCode::firstOrCreate(['code' => '06'],  ['name' => 'SUBCONTRACTOR', 'sort_order' => 14]);
        $cc06a = CostCode::firstOrCreate(['code' => '06A'], ['name' => 'AI Package and Code Work Package', 'parent_id' => $cc06->id, 'sort_order' => 15]);
        $cc06b = CostCode::firstOrCreate(['code' => '06B'], ['name' => 'WEEKLY - PT Test - Working Stright Days', 'parent_id' => $cc06->id, 'sort_order' => 16]);
        $cc06c = CostCode::firstOrCreate(['code' => '06C'], ['name' => 'Buckhorn', 'parent_id' => $cc06->id, 'sort_order' => 17]);
        $cc06d = CostCode::firstOrCreate(['code' => '06D'], ['name' => "Tax's", 'parent_id' => $cc06->id, 'sort_order' => 18]);
        $cc06e = CostCode::firstOrCreate(['code' => '06E'], ['name' => 'Onboarding / Mobilization Cost', 'parent_id' => $cc06->id, 'sort_order' => 19]);
        $cc06f = CostCode::firstOrCreate(['code' => '06F'], ['name' => 'MSHA Class', 'parent_id' => $cc06->id, 'sort_order' => 20]);

        $cc07 = CostCode::firstOrCreate(['code' => '07'], ['name' => 'EQUIPMENT COST', 'sort_order' => 21]);
        $cc08 = CostCode::firstOrCreate(['code' => '08'], ['name' => 'PER DIEM', 'sort_order' => 22]);
        $cc09 = CostCode::firstOrCreate(['code' => '09'], ['name' => 'NON-REIMBURSEABLE', 'sort_order' => 23]);
        $cc13 = CostCode::firstOrCreate(['code' => '13'], ['name' => 'SALES TAX', 'sort_order' => 24]);

        // 7. VENDORS
        $vendors = [];
        $vendorData = [
            ['name' => 'Buckhorn Industrial', 'type' => 'subcontractor', 'city' => 'Baton Rouge', 'state' => 'LA'],
            ['name' => 'PT Testing Services', 'type' => 'subcontractor', 'city' => 'Gramercy', 'state' => 'LA'],
            ['name' => 'AI Package Solutions', 'type' => 'subcontractor', 'city' => 'New Orleans', 'state' => 'LA'],
            ['name' => 'United Rentals', 'type' => 'rental', 'city' => 'Baton Rouge', 'state' => 'LA'],
            ['name' => 'Ferguson Enterprises', 'type' => 'supplier', 'city' => 'Baton Rouge', 'state' => 'LA'],
            ['name' => 'MSHA Training Corp', 'type' => 'other', 'city' => 'Metairie', 'state' => 'LA'],
        ];
        foreach ($vendorData as $v) {
            $vendors[$v['name']] = Vendor::firstOrCreate(['name' => $v['name']], $v);
        }

        // 8. PROJECTS
        $project1 = Project::firstOrCreate(
            ['project_number' => 'BM-5403'],
            [
                'name' => 'HI3 Heater Repairs',
                'client_id' => $client1->id,
                'address' => '1111 East Airline Hwy',
                'city' => 'Gramercy',
                'state' => 'LA',
                'zip' => '70752',
                'status' => 'active',
                'start_date' => '2026-01-15',
                'end_date' => '2026-06-30',
                'substantial_completion_date' => '2026-04-13',
                'original_budget' => 456487.52,
                'current_budget' => 543277.61,
                'estimate' => 538865.74,
                'contract_value' => 538865.52,
                'po_number' => 'PO-2026-001',
                'po_date' => '2026-03-02',
            ]
        );

        $project2 = Project::firstOrCreate(
            ['project_number' => 'GC-7201'],
            [
                'name' => 'Turnaround Support - Unit 4',
                'client_id' => $client2->id,
                'address' => '4500 River Road',
                'city' => 'Baton Rouge',
                'state' => 'LA',
                'zip' => '70801',
                'status' => 'active',
                'start_date' => '2026-02-01',
                'end_date' => '2026-08-31',
                'original_budget' => 850000.00,
                'current_budget' => 875000.00,
                'estimate' => 920000.00,
                'contract_value' => 920000.00,
            ]
        );

        // 9. BUDGET LINES for BM-5403
        $budgetData = [
            ['cost_code_id' => $cc01->id, 'budget_amount' => 138276.80],
            ['cost_code_id' => $cc010->id, 'budget_amount' => 134957.12],
            ['cost_code_id' => $cc02->id, 'budget_amount' => 0],
            ['cost_code_id' => $cc03->id, 'budget_amount' => 16539.60],
            ['cost_code_id' => $cc03a->id, 'budget_amount' => 8269.80],
            ['cost_code_id' => $cc03b->id, 'budget_amount' => 2614.92],
            ['cost_code_id' => $cc03c->id, 'budget_amount' => 5654.88],
            ['cost_code_id' => $cc03f->id, 'budget_amount' => 5375.37],
            ['cost_code_id' => $cc04->id, 'budget_amount' => 16800.00],
            ['cost_code_id' => $cc04a->id, 'budget_amount' => 6000.00],
            ['cost_code_id' => $cc05->id, 'budget_amount' => 9598.00],
            ['cost_code_id' => $cc06->id, 'budget_amount' => 100023.00],
            ['cost_code_id' => $cc06a->id, 'budget_amount' => 2500.00],
            ['cost_code_id' => $cc06b->id, 'budget_amount' => 15500.00],
            ['cost_code_id' => $cc06c->id, 'budget_amount' => 61803.00],
            ['cost_code_id' => $cc06d->id, 'budget_amount' => 9493.00],
            ['cost_code_id' => $cc06e->id, 'budget_amount' => 7127.00],
            ['cost_code_id' => $cc06f->id, 'budget_amount' => 3600.00],
            ['cost_code_id' => $cc08->id, 'budget_amount' => 30800.00],
            ['cost_code_id' => $cc09->id, 'budget_amount' => 0],
            ['cost_code_id' => $cc13->id, 'budget_amount' => 9493.00],
        ];
        foreach ($budgetData as $bl) {
            BudgetLine::firstOrCreate(
                ['project_id' => $project1->id, 'cost_code_id' => $bl['cost_code_id']],
                $bl
            );
        }

        // 10. COMMITMENTS
        $commitmentData = [
            ['cost_code_id' => $cc01->id, 'description' => 'Direct Labor Commitment', 'amount' => 59941.71, 'vendor_id' => null],
            ['cost_code_id' => $cc010->id, 'description' => 'Indirect Labor Commitment', 'amount' => 51334.53, 'vendor_id' => null],
            ['cost_code_id' => $cc03a->id, 'description' => 'Welding Machine Rental', 'amount' => 4349.10, 'vendor_id' => $vendors['United Rentals']->id],
            ['cost_code_id' => $cc03d->id, 'description' => 'Restrooms', 'amount' => 1139.06, 'vendor_id' => $vendors['United Rentals']->id],
            ['cost_code_id' => $cc03e->id, 'description' => 'UTV Rental', 'amount' => 1187.02, 'vendor_id' => $vendors['United Rentals']->id],
            ['cost_code_id' => $cc05->id, 'description' => 'Consumables & Supplies', 'amount' => 11863.82, 'vendor_id' => $vendors['Ferguson Enterprises']->id],
            ['cost_code_id' => $cc06b->id, 'description' => 'Weekly PT Testing', 'amount' => 1037.82, 'vendor_id' => $vendors['PT Testing Services']->id],
            ['cost_code_id' => $cc06e->id, 'description' => 'Onboarding/Mobilization', 'amount' => 750.00, 'vendor_id' => $vendors['AI Package Solutions']->id],
            ['cost_code_id' => $cc06f->id, 'description' => 'MSHA Safety Class', 'amount' => 5450.00, 'vendor_id' => $vendors['MSHA Training Corp']->id],
            ['cost_code_id' => $cc08->id, 'description' => 'Per Diem', 'amount' => 7400.00, 'vendor_id' => null],
            ['cost_code_id' => $cc09->id, 'description' => 'Non-Reimbursable Costs', 'amount' => 427.39, 'vendor_id' => null],
        ];
        foreach ($commitmentData as $cm) {
            Commitment::firstOrCreate(
                ['project_id' => $project1->id, 'cost_code_id' => $cm['cost_code_id'], 'description' => $cm['description']],
                array_merge($cm, [
                    'project_id' => $project1->id,
                    'committed_date' => now()->subDays(rand(10, 60)),
                    'status' => 'approved',
                ])
            );
        }

        // 11. INVOICES
        $invoiceData = [
            ['cost_code_id' => $cc01->id, 'description' => 'Direct Labor Invoice', 'amount' => 79030.15, 'invoice_number' => 'INV-DL-001'],
            ['cost_code_id' => $cc010->id, 'description' => 'Indirect Labor Invoice', 'amount' => 66876.02, 'invoice_number' => 'INV-IL-001'],
            ['cost_code_id' => $cc03->id, 'description' => '3rd Party Rental Invoice', 'amount' => 10750.74, 'invoice_number' => 'INV-3P-001'],
            ['cost_code_id' => $cc04->id, 'description' => 'Company Equipment Invoice', 'amount' => 12000.00, 'invoice_number' => 'INV-CE-001'],
            ['cost_code_id' => $cc05->id, 'description' => 'Consumables Invoice', 'amount' => 5775.24, 'invoice_number' => 'INV-CS-001'],
            ['cost_code_id' => $cc06->id, 'description' => 'Subcontractor Invoice', 'amount' => 13820.00, 'invoice_number' => 'INV-SC-001'],
            ['cost_code_id' => $cc08->id, 'description' => 'Per Diem Invoice', 'amount' => 7400.00, 'invoice_number' => 'INV-PD-001'],
        ];
        foreach ($invoiceData as $inv) {
            Invoice::firstOrCreate(
                ['invoice_number' => $inv['invoice_number']],
                array_merge($inv, [
                    'project_id' => $project1->id,
                    'invoice_date' => now()->subDays(rand(5, 30)),
                    'status' => 'approved',
                ])
            );
        }

        // 12. CHANGE ORDERS
        $coData = [
            ['co_number' => '001', 'description' => 'Discovery work associated with heater repair', 'amount' => 9896.60, 'status' => 'approved'],
            ['co_number' => '002', 'description' => 'Discovery work associated with heater repair', 'amount' => 3794.23, 'status' => 'approved'],
            ['co_number' => '003', 'description' => 'Discovery work associated with heater repair', 'amount' => 658.16, 'status' => 'approved'],
            ['co_number' => '004', 'description' => 'Valves linear indication', 'amount' => 697.14, 'status' => 'approved'],
            ['co_number' => '005', 'description' => 'Head Repair', 'amount' => 22077.56, 'status' => 'voided'],
            ['co_number' => '006', 'description' => 'Flat Face Flanges', 'amount' => 49666.40, 'status' => 'approved'],
            ['co_number' => '007', 'description' => 'Change 2" 150lbs Valves', 'amount' => 20898.15, 'status' => 'pending', 'scope_of_work' => 'Furnish straight-time labor, supervision, materials, and equipment associated with this work.', 'contract_time_change_days' => 1, 'new_completion_date' => '2026-04-13'],
        ];
        foreach ($coData as $co) {
            $newCompletionDate = $co['new_completion_date'] ?? null;
            $contractTimeChangeDays = $co['contract_time_change_days'] ?? 0;
            $scopeOfWork = $co['scope_of_work'] ?? null;
            $coNumber = $co['co_number'];

            unset($co['new_completion_date'], $co['contract_time_change_days'], $co['scope_of_work']);

            $changeOrder = ChangeOrder::firstOrCreate(
                ['project_id' => $project1->id, 'co_number' => $coNumber],
                array_merge($co, [
                    'project_id' => $project1->id,
                    'date' => '2026-03-25',
                    'scope_of_work' => $scopeOfWork,
                    'contract_time_change_days' => $contractTimeChangeDays,
                    'new_completion_date' => $newCompletionDate,
                ])
            );

            // Add labor details for CO 007
            if ($coNumber === '007') {
                ChangeOrderLabor::firstOrCreate(
                    ['change_order_id' => $changeOrder->id, 'skill_description' => 'Pipe Fitter', 'is_overtime' => false],
                    ['craft_id' => $crafts['PF']->id, 'num_workers' => 2, 'rate_per_hour' => 62.62, 'hours_per_day' => 10, 'duration_days' => 3.0, 'cost' => 3757.20]
                );
                ChangeOrderLabor::firstOrCreate(
                    ['change_order_id' => $changeOrder->id, 'skill_description' => 'QA/QC', 'is_overtime' => false],
                    ['craft_id' => $crafts['QA']->id, 'num_workers' => 1, 'rate_per_hour' => 74.55, 'hours_per_day' => 3, 'duration_days' => 3.0, 'cost' => 670.95]
                );
                ChangeOrderItem::firstOrCreate(
                    ['change_order_id' => $changeOrder->id, 'description' => 'Pipe Material (including bolts, gaskets & valves)'],
                    ['category' => 'material', 'quantity' => 1, 'unit_cost' => 15870.00, 'amount' => 15870.00]
                );
                ChangeOrderItem::firstOrCreate(
                    ['change_order_id' => $changeOrder->id, 'description' => 'Per Diem (2 men 3 days)'],
                    ['category' => 'other', 'quantity' => 1, 'unit_cost' => 600.00, 'amount' => 600.00]
                );
            }
        }

        // 13. CREWS
        $crew1 = Crew::firstOrCreate(
            ['name' => 'Crew A - Pipe', 'project_id' => $project1->id],
            ['foreman_id' => $employees['E002']->id, 'shift_id' => $dayShift->id]
        );
        foreach (['E003', 'E004', 'E005', 'E007'] as $empNum) {
            CrewMember::firstOrCreate(
                ['crew_id' => $crew1->id, 'employee_id' => $employees[$empNum]->id],
                ['assigned_date' => '2026-01-20']
            );
        }

        $crew2 = Crew::firstOrCreate(
            ['name' => 'Crew B - QA/Electrical', 'project_id' => $project1->id],
            ['foreman_id' => $employees['E001']->id, 'shift_id' => $dayShift->id]
        );
        foreach (['E006', 'E008', 'E009', 'E011'] as $empNum) {
            CrewMember::firstOrCreate(
                ['crew_id' => $crew2->id, 'employee_id' => $employees[$empNum]->id],
                ['assigned_date' => '2026-01-20']
            );
        }

        // 14. TIMESHEETS (30 days of sample data)
        $tsEmployees = ['E001','E002','E003','E004','E005','E006','E007','E008','E009','E011'];
        for ($day = 0; $day < 30; $day++) {
            $date = now()->subDays($day);
            if ($date->isWeekend()) continue;

            foreach ($tsEmployees as $empNum) {
                $emp = $employees[$empNum];
                $regHours = rand(8, 10) * 1.0;
                $otHours = rand(0, 2) * 0.5;
                $totalHours = $regHours + $otHours;
                $totalCost = ($regHours * $emp->hourly_rate) + ($otHours * $emp->overtime_rate);
                $billableAmount = $totalHours * $emp->billable_rate;

                Timesheet::firstOrCreate(
                    ['employee_id' => $emp->id, 'project_id' => $project1->id, 'date' => $date->format('Y-m-d')],
                    [
                        'crew_id' => in_array($empNum, ['E003','E004','E005','E007']) ? $crew1->id : $crew2->id,
                        'shift_id' => $dayShift->id,
                        'regular_hours' => $regHours,
                        'overtime_hours' => $otHours,
                        'double_time_hours' => 0,
                        'total_hours' => $totalHours,
                        'regular_rate' => $emp->hourly_rate,
                        'overtime_rate' => $emp->overtime_rate,
                        'total_cost' => $totalCost,
                        'billable_rate' => $emp->billable_rate,
                        'billable_amount' => $billableAmount,
                        'status' => $day > 7 ? 'approved' : 'submitted',
                        'approved_by' => $day > 7 ? $admin->id : null,
                        'approved_at' => $day > 7 ? now()->subDays($day - 1) : null,
                    ]
                );
            }
        }

        // 15. MANHOUR BUDGETS
        ManhourBudget::firstOrCreate(
            ['project_id' => $project1->id, 'cost_code_id' => $cc01->id, 'category' => 'direct'],
            ['budget_hours' => 2950, 'earned_hours' => 1122.65]
        );
        ManhourBudget::firstOrCreate(
            ['project_id' => $project1->id, 'cost_code_id' => $cc010->id, 'category' => 'indirect'],
            ['budget_hours' => 1877, 'earned_hours' => 0]
        );

        // 16. ESTIMATES
        $estimate = Estimate::firstOrCreate(
            ['project_id' => $project1->id, 'estimate_number' => 'EST-001'],
            ['name' => 'Original Estimate - HI3 Heater Repairs', 'total_amount' => 538865.74, 'status' => 'approved', 'created_by' => $admin->id]
        );
        $estimateLines = [
            ['cost_code_id' => $cc01->id, 'description' => 'Direct Labor', 'amount' => 145000.00, 'labor_hours' => 2950],
            ['cost_code_id' => $cc010->id, 'description' => 'Indirect Labor', 'amount' => 140000.00, 'labor_hours' => 1877],
            ['cost_code_id' => $cc03->id, 'description' => '3rd Party Rentals', 'amount' => 18000.00, 'labor_hours' => 0],
            ['cost_code_id' => $cc06->id, 'description' => 'Subcontractors', 'amount' => 120000.00, 'labor_hours' => 0],
            ['cost_code_id' => $cc08->id, 'description' => 'Per Diem', 'amount' => 35000.00, 'labor_hours' => 0],
        ];
        foreach ($estimateLines as $el) {
            EstimateLine::firstOrCreate(
                ['estimate_id' => $estimate->id, 'cost_code_id' => $el['cost_code_id']],
                $el
            );
        }

        // 17. PER DIEM RATES
        PerDiemRate::firstOrCreate(
            ['project_id' => $project1->id, 'description' => 'Standard Per Diem'],
            ['daily_rate' => 100.00]
        );
        PerDiemRate::firstOrCreate(
            ['project_id' => $project1->id, 'description' => 'Travel Day Per Diem'],
            ['daily_rate' => 50.00]
        );

        // 18. PAYROLL PERIOD
        PayrollPeriod::firstOrCreate(
            ['start_date' => now()->subDays(14)->startOfWeek()->format('Y-m-d'), 'end_date' => now()->subDays(14)->endOfWeek()->format('Y-m-d')],
            ['status' => 'processed', 'processed_at' => now()->subDays(7)]
        );

        // 19. DAILY LOGS
        $weathers = ['Clear', 'Partly Cloudy', 'Overcast', 'Rain', 'Clear'];
        for ($d = 0; $d < 5; $d++) {
            DailyLog::firstOrCreate(
                ['project_id' => $project1->id, 'date' => now()->subDays($d)->format('Y-m-d')],
                [
                    'weather' => $weathers[$d],
                    'temperature' => rand(65, 85) . '°F',
                    'notes' => 'Normal operations. Crew fully staffed. No safety incidents.',
                    'created_by' => $admin->id,
                ]
            );
        }

        // 20. EQUIPMENT
        $welder = Equipment::firstOrCreate(
            ['name' => 'Lincoln Welder SA-200'],
            ['type' => 'rented', 'daily_rate' => 150.00, 'weekly_rate' => 750.00, 'vendor_id' => $vendors['United Rentals']->id, 'status' => 'in_use']
        );
        $forklift = Equipment::firstOrCreate(
            ['name' => 'CAT TL642 Telehandler'],
            ['type' => 'rented', 'daily_rate' => 200.00, 'weekly_rate' => 1000.00, 'vendor_id' => $vendors['United Rentals']->id, 'status' => 'in_use']
        );
        $generator = Equipment::firstOrCreate(
            ['name' => 'Generator 20kW'],
            ['type' => 'rented', 'daily_rate' => 100.00, 'weekly_rate' => 500.00, 'vendor_id' => $vendors['United Rentals']->id, 'status' => 'in_use']
        );

        EquipmentAssignment::firstOrCreate(
            ['equipment_id' => $welder->id, 'project_id' => $project1->id],
            ['assigned_date' => '2026-01-20', 'daily_cost' => 150.00]
        );
        EquipmentAssignment::firstOrCreate(
            ['equipment_id' => $forklift->id, 'project_id' => $project1->id],
            ['assigned_date' => '2026-01-20', 'daily_cost' => 200.00]
        );
        EquipmentAssignment::firstOrCreate(
            ['equipment_id' => $generator->id, 'project_id' => $project1->id],
            ['assigned_date' => '2026-02-01', 'daily_cost' => 100.00]
        );

        // 21. MATERIALS
        $valves = Material::firstOrCreate(
            ['name' => 'FNW 600C Ball Valve 2"'],
            ['description' => 'Stainless Steel Ball Valve 2PC Full Port 150# Flanged', 'unit_of_measure' => 'EA', 'unit_cost' => 470.00, 'vendor_id' => $vendors['Ferguson Enterprises']->id, 'category' => 'Valves']
        );
        $gasket = Material::firstOrCreate(
            ['name' => 'Garlock Blue-Gard 3400'],
            ['description' => 'Gasket Material - Aramid fibers with SBR binder', 'unit_of_measure' => 'EA', 'unit_cost' => 45.00, 'vendor_id' => $vendors['Ferguson Enterprises']->id, 'category' => 'Gaskets']
        );
        Material::firstOrCreate(
            ['name' => 'A193-B8 Stud Bolts'],
            ['description' => 'Stainless Steel Stud Bolts', 'unit_of_measure' => 'SET', 'unit_cost' => 25.00, 'vendor_id' => $vendors['Ferguson Enterprises']->id, 'category' => 'Fasteners']
        );

        MaterialUsage::firstOrCreate(
            ['project_id' => $project1->id, 'material_id' => $valves->id, 'date' => '2026-03-20'],
            ['cost_code_id' => $cc02->id, 'description' => 'Valve replacement on HI3', 'quantity' => 4, 'unit_cost' => 470.00, 'total_cost' => 1880.00]
        );
        MaterialUsage::firstOrCreate(
            ['project_id' => $project1->id, 'material_id' => $gasket->id, 'date' => '2026-03-20'],
            ['cost_code_id' => $cc02->id, 'description' => 'Gaskets for valve flanges', 'quantity' => 8, 'unit_cost' => 45.00, 'total_cost' => 360.00]
        );

        // 22. BILLING INVOICE
        BillingInvoice::firstOrCreate(
            ['invoice_number' => 'BILL-2026-001'],
            [
                'project_id' => $project1->id,
                'billing_period_start' => '2026-02-01',
                'billing_period_end' => '2026-02-28',
                'labor_amount' => 85000.00,
                'material_amount' => 2240.00,
                'equipment_amount' => 5500.00,
                'subcontractor_amount' => 13820.00,
                'other_amount' => 7400.00,
                'subtotal' => 113960.00,
                'tax_rate' => 0.0000,
                'tax_amount' => 0.00,
                'total_amount' => 113960.00,
                'status' => 'sent',
                'sent_date' => '2026-03-05',
            ]
        );
    }
}
