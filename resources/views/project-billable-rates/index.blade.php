@extends('layouts.app')

@section('title', 'Project Billable Rates - ' . $project->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">← Back to {{ $project->name }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Project Billable Rates</h1>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Billable Rate
            </button>
        </div>
    </div>

    <!-- Rates Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <table id="ratesTable" class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Craft</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Employee</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Base Rate</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Markup %</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Straight Rate</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">OT Rate</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Effective Date</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
        <h2 class="text-2xl font-bold mb-6">Add Billable Rate</h2>

        <form id="createForm" method="POST" action="{{ route('projects.billable-rates.store', $project) }}">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="create_craft_id" class="block text-sm font-medium text-gray-700 mb-2">Craft</label>
                    <select name="craft_id" id="create_craft_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                        <option value="">Select Craft (Optional)</option>
                    </select>
                </div>

                <div>
                    <label for="create_employee_id" class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                    <select name="employee_id" id="create_employee_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                        <option value="">Select Employee (Optional)</option>
                    </select>
                </div>

                <div>
                    <label for="create_base_hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">Base Hourly Rate *</label>
                    <input type="number" name="base_hourly_rate" id="create_base_hourly_rate" step="0.01" min="0" required class="w-full border-gray-300 rounded-lg shadow-sm" placeholder="0.00">
                </div>

                <div>
                    <label for="create_effective_date" class="block text-sm font-medium text-gray-700 mb-2">Effective Date *</label>
                    <input type="date" name="effective_date" id="create_effective_date" required class="w-full border-gray-300 rounded-lg shadow-sm">
                </div>

                <!-- Markup Rate Fields -->
                <div>
                    <label for="create_payroll_tax_rate" class="block text-sm font-medium text-gray-700 mb-2">Payroll Tax %</label>
                    <input type="number" name="payroll_tax_rate" id="create_payroll_tax_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm create-rate-input" placeholder="0.00" data-rate="payroll_tax_rate">
                </div>

                <div>
                    <label for="create_burden_rate" class="block text-sm font-medium text-gray-700 mb-2">Burden %</label>
                    <input type="number" name="burden_rate" id="create_burden_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm create-rate-input" placeholder="0.00" data-rate="burden_rate">
                </div>

                <div>
                    <label for="create_insurance_rate" class="block text-sm font-medium text-gray-700 mb-2">Insurance %</label>
                    <input type="number" name="insurance_rate" id="create_insurance_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm create-rate-input" placeholder="0.00" data-rate="insurance_rate">
                </div>

                <div>
                    <label for="create_job_expenses_rate" class="block text-sm font-medium text-gray-700 mb-2">Job Expenses %</label>
                    <input type="number" name="job_expenses_rate" id="create_job_expenses_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm create-rate-input" placeholder="0.00" data-rate="job_expenses_rate">
                </div>

                <div>
                    <label for="create_consumables_rate" class="block text-sm font-medium text-gray-700 mb-2">Consumables %</label>
                    <input type="number" name="consumables_rate" id="create_consumables_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm create-rate-input" placeholder="0.00" data-rate="consumables_rate">
                </div>

                <div>
                    <label for="create_overhead_rate" class="block text-sm font-medium text-gray-700 mb-2">Overhead %</label>
                    <input type="number" name="overhead_rate" id="create_overhead_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm create-rate-input" placeholder="0.00" data-rate="overhead_rate">
                </div>

                <div>
                    <label for="create_profit_rate" class="block text-sm font-medium text-gray-700 mb-2">Profit %</label>
                    <input type="number" name="profit_rate" id="create_profit_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm create-rate-input" placeholder="0.00" data-rate="profit_rate">
                </div>
            </div>

            <!-- Auto-calculated Preview -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Calculated Rates Preview</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Straight Time Rate</p>
                        <p id="create_st_rate_preview" class="text-lg font-bold text-gray-900">$0.00</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Overtime Rate (1.5x)</p>
                        <p id="create_ot_rate_preview" class="text-lg font-bold text-gray-900">$0.00</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Double Time Rate (2x)</p>
                        <p id="create_dt_rate_preview" class="text-lg font-bold text-gray-900">$0.00</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex-1">
                    Add Rate
                </button>
                <button type="button" onclick="closeCreateModal()" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded flex-1">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
        <h2 class="text-2xl font-bold mb-6">Edit Billable Rate</h2>

        <form id="editForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit_craft_id" class="block text-sm font-medium text-gray-700 mb-2">Craft</label>
                    <select name="craft_id" id="edit_craft_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                        <option value="">Select Craft (Optional)</option>
                    </select>
                </div>

                <div>
                    <label for="edit_employee_id" class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                    <select name="employee_id" id="edit_employee_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                        <option value="">Select Employee (Optional)</option>
                    </select>
                </div>

                <div>
                    <label for="edit_base_hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">Base Hourly Rate *</label>
                    <input type="number" name="base_hourly_rate" id="edit_base_hourly_rate" step="0.01" min="0" required class="w-full border-gray-300 rounded-lg shadow-sm" placeholder="0.00">
                </div>

                <div>
                    <label for="edit_effective_date" class="block text-sm font-medium text-gray-700 mb-2">Effective Date *</label>
                    <input type="date" name="effective_date" id="edit_effective_date" required class="w-full border-gray-300 rounded-lg shadow-sm">
                </div>

                <!-- Markup Rate Fields -->
                <div>
                    <label for="edit_payroll_tax_rate" class="block text-sm font-medium text-gray-700 mb-2">Payroll Tax %</label>
                    <input type="number" name="payroll_tax_rate" id="edit_payroll_tax_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm edit-rate-input" placeholder="0.00" data-rate="payroll_tax_rate">
                </div>

                <div>
                    <label for="edit_burden_rate" class="block text-sm font-medium text-gray-700 mb-2">Burden %</label>
                    <input type="number" name="burden_rate" id="edit_burden_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm edit-rate-input" placeholder="0.00" data-rate="burden_rate">
                </div>

                <div>
                    <label for="edit_insurance_rate" class="block text-sm font-medium text-gray-700 mb-2">Insurance %</label>
                    <input type="number" name="insurance_rate" id="edit_insurance_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm edit-rate-input" placeholder="0.00" data-rate="insurance_rate">
                </div>

                <div>
                    <label for="edit_job_expenses_rate" class="block text-sm font-medium text-gray-700 mb-2">Job Expenses %</label>
                    <input type="number" name="job_expenses_rate" id="edit_job_expenses_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm edit-rate-input" placeholder="0.00" data-rate="job_expenses_rate">
                </div>

                <div>
                    <label for="edit_consumables_rate" class="block text-sm font-medium text-gray-700 mb-2">Consumables %</label>
                    <input type="number" name="consumables_rate" id="edit_consumables_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm edit-rate-input" placeholder="0.00" data-rate="consumables_rate">
                </div>

                <div>
                    <label for="edit_overhead_rate" class="block text-sm font-medium text-gray-700 mb-2">Overhead %</label>
                    <input type="number" name="overhead_rate" id="edit_overhead_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm edit-rate-input" placeholder="0.00" data-rate="overhead_rate">
                </div>

                <div>
                    <label for="edit_profit_rate" class="block text-sm font-medium text-gray-700 mb-2">Profit %</label>
                    <input type="number" name="profit_rate" id="edit_profit_rate" step="0.0001" min="0" max="1" class="w-full border-gray-300 rounded-lg shadow-sm edit-rate-input" placeholder="0.00" data-rate="profit_rate">
                </div>
            </div>

            <!-- Auto-calculated Preview -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Calculated Rates Preview</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Straight Time Rate</p>
                        <p id="edit_st_rate_preview" class="text-lg font-bold text-gray-900">$0.00</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Overtime Rate (1.5x)</p>
                        <p id="edit_ot_rate_preview" class="text-lg font-bold text-gray-900">$0.00</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Double Time Rate (2x)</p>
                        <p id="edit_dt_rate_preview" class="text-lg font-bold text-gray-900">$0.00</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex-1">
                    Update Rate
                </button>
                <button type="button" onclick="closeEditModal()" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded flex-1">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold mb-6">Billable Rate Details</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Craft</label>
                <p id="view_craft" class="text-gray-900">—</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                <p id="view_employee" class="text-gray-900">—</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Base Hourly Rate</label>
                <p id="view_base_rate" class="text-gray-900 font-semibold">$0.00</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Markup Percentage</label>
                <p id="view_markup" class="text-gray-900 font-semibold">0.00%</p>
            </div>

            <div class="border-t pt-4 mt-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Calculated Rates</h3>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Straight Time</p>
                        <p id="view_st_rate" class="text-base font-bold text-gray-900">$0.00</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Overtime (1.5x)</p>
                        <p id="view_ot_rate" class="text-base font-bold text-gray-900">$0.00</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Double Time (2x)</p>
                        <p id="view_dt_rate" class="text-base font-bold text-gray-900">$0.00</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Effective Date</label>
                <p id="view_effective_date" class="text-gray-900">—</p>
            </div>
        </div>

        <div class="mt-6">
            <button type="button" onclick="closeViewModal()" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded w-full">
                Close
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var currentRateId = null;
    var projectId = {{ $project->id }};

    // Modal functions
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
        loadCreateSelectOptions();
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
        document.getElementById('createForm').reset();
        document.getElementById('create_st_rate_preview').textContent = '$0.00';
        document.getElementById('create_ot_rate_preview').textContent = '$0.00';
        document.getElementById('create_dt_rate_preview').textContent = '$0.00';
    }

    function openEditModal() {
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editForm').reset();
        document.getElementById('edit_st_rate_preview').textContent = '$0.00';
        document.getElementById('edit_ot_rate_preview').textContent = '$0.00';
        document.getElementById('edit_dt_rate_preview').textContent = '$0.00';
        currentRateId = null;
    }

    function openViewModal() {
        document.getElementById('viewModal').classList.remove('hidden');
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }

    // Close modal on background click
    document.getElementById('createModal').addEventListener('click', function(e) {
        if (e.target === this) closeCreateModal();
    });

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });

    document.getElementById('viewModal').addEventListener('click', function(e) {
        if (e.target === this) closeViewModal();
    });

    // Load craft and employee options for create modal
    function loadCreateSelectOptions() {
        $.get('/crafts', function(data) {
            let options = '<option value="">Select Craft (Optional)</option>';
            data.forEach(function(craft) {
                options += `<option value="${craft.id}">${craft.name}</option>`;
            });
            $('#create_craft_id').html(options);
        });

        $.get('/employees', function(data) {
            let options = '<option value="">Select Employee (Optional)</option>';
            data.forEach(function(employee) {
                options += `<option value="${employee.id}">${employee.first_name} ${employee.last_name}</option>`;
            });
            $('#create_employee_id').html(options);
        });
    }

    // Load craft and employee options for edit modal
    function loadEditSelectOptions() {
        $.get('/crafts', function(data) {
            let options = '<option value="">Select Craft (Optional)</option>';
            data.forEach(function(craft) {
                options += `<option value="${craft.id}">${craft.name}</option>`;
            });
            $('#edit_craft_id').html(options);
        });

        $.get('/employees', function(data) {
            let options = '<option value="">Select Employee (Optional)</option>';
            data.forEach(function(employee) {
                options += `<option value="${employee.id}">${employee.first_name} ${employee.last_name}</option>`;
            });
            $('#edit_employee_id').html(options);
        });
    }

    // Calculate and update preview rates for create form
    function updateCreatePreview() {
        const baseRate = parseFloat(document.getElementById('create_base_hourly_rate').value) || 0;
        const payrollTax = parseFloat(document.getElementById('create_payroll_tax_rate').value) || 0;
        const burden = parseFloat(document.getElementById('create_burden_rate').value) || 0;
        const insurance = parseFloat(document.getElementById('create_insurance_rate').value) || 0;
        const jobExpenses = parseFloat(document.getElementById('create_job_expenses_rate').value) || 0;
        const consumables = parseFloat(document.getElementById('create_consumables_rate').value) || 0;
        const overhead = parseFloat(document.getElementById('create_overhead_rate').value) || 0;
        const profit = parseFloat(document.getElementById('create_profit_rate').value) || 0;

        const totalMarkup = payrollTax + burden + insurance + jobExpenses + consumables + overhead + profit;
        const straightTimeRate = baseRate * (1 + totalMarkup);
        const overtimeRate = straightTimeRate * 1.5;
        const doubleTimeRate = straightTimeRate * 2;

        document.getElementById('create_st_rate_preview').textContent = '$' + straightTimeRate.toFixed(2);
        document.getElementById('create_ot_rate_preview').textContent = '$' + overtimeRate.toFixed(2);
        document.getElementById('create_dt_rate_preview').textContent = '$' + doubleTimeRate.toFixed(2);
    }

    // Calculate and update preview rates for edit form
    function updateEditPreview() {
        const baseRate = parseFloat(document.getElementById('edit_base_hourly_rate').value) || 0;
        const payrollTax = parseFloat(document.getElementById('edit_payroll_tax_rate').value) || 0;
        const burden = parseFloat(document.getElementById('edit_burden_rate').value) || 0;
        const insurance = parseFloat(document.getElementById('edit_insurance_rate').value) || 0;
        const jobExpenses = parseFloat(document.getElementById('edit_job_expenses_rate').value) || 0;
        const consumables = parseFloat(document.getElementById('edit_consumables_rate').value) || 0;
        const overhead = parseFloat(document.getElementById('edit_overhead_rate').value) || 0;
        const profit = parseFloat(document.getElementById('edit_profit_rate').value) || 0;

        const totalMarkup = payrollTax + burden + insurance + jobExpenses + consumables + overhead + profit;
        const straightTimeRate = baseRate * (1 + totalMarkup);
        const overtimeRate = straightTimeRate * 1.5;
        const doubleTimeRate = straightTimeRate * 2;

        document.getElementById('edit_st_rate_preview').textContent = '$' + straightTimeRate.toFixed(2);
        document.getElementById('edit_ot_rate_preview').textContent = '$' + overtimeRate.toFixed(2);
        document.getElementById('edit_dt_rate_preview').textContent = '$' + doubleTimeRate.toFixed(2);
    }

    // Attach event listeners for create form inputs
    document.getElementById('create_base_hourly_rate').addEventListener('change', updateCreatePreview);
    document.getElementById('create_base_hourly_rate').addEventListener('keyup', updateCreatePreview);
    document.querySelectorAll('.create-rate-input').forEach(function(input) {
        input.addEventListener('change', updateCreatePreview);
        input.addEventListener('keyup', updateCreatePreview);
    });

    // Attach event listeners for edit form inputs
    document.getElementById('edit_base_hourly_rate').addEventListener('change', updateEditPreview);
    document.getElementById('edit_base_hourly_rate').addEventListener('keyup', updateEditPreview);
    document.querySelectorAll('.edit-rate-input').forEach(function(input) {
        input.addEventListener('change', updateEditPreview);
        input.addEventListener('keyup', updateEditPreview);
    });

    // Edit rate
    function editRate(id) {
        currentRateId = id;
        $.get(`/projects/${projectId}/billable-rates/${id}/edit`, function(data) {
            loadEditSelectOptions();

            $('#edit_craft_id').val(data.craft_id || '');
            $('#edit_employee_id').val(data.employee_id || '');
            $('#edit_base_hourly_rate').val(parseFloat(data.base_hourly_rate).toFixed(2));
            $('#edit_payroll_tax_rate').val(parseFloat(data.payroll_tax_rate || 0).toFixed(4));
            $('#edit_burden_rate').val(parseFloat(data.burden_rate || 0).toFixed(4));
            $('#edit_insurance_rate').val(parseFloat(data.insurance_rate || 0).toFixed(4));
            $('#edit_job_expenses_rate').val(parseFloat(data.job_expenses_rate || 0).toFixed(4));
            $('#edit_consumables_rate').val(parseFloat(data.consumables_rate || 0).toFixed(4));
            $('#edit_overhead_rate').val(parseFloat(data.overhead_rate || 0).toFixed(4));
            $('#edit_profit_rate').val(parseFloat(data.profit_rate || 0).toFixed(4));
            $('#edit_effective_date').val(data.effective_date);

            updateEditPreview();
            openEditModal();
        });
    }

    // View rate
    function viewRate(id) {
        $.get(`/projects/${projectId}/billable-rates/${id}/edit`, function(data) {
            const craftName = data.craft ? data.craft.name : '—';
            const employeeName = data.employee ? `${data.employee.first_name} ${data.employee.last_name}` : '—';
            const markupPercent = ((parseFloat(data.payroll_tax_rate || 0) +
                                    parseFloat(data.burden_rate || 0) +
                                    parseFloat(data.insurance_rate || 0) +
                                    parseFloat(data.job_expenses_rate || 0) +
                                    parseFloat(data.consumables_rate || 0) +
                                    parseFloat(data.overhead_rate || 0) +
                                    parseFloat(data.profit_rate || 0)) * 100).toFixed(2);

            $('#view_craft').text(craftName);
            $('#view_employee').text(employeeName);
            $('#view_base_rate').text('$' + parseFloat(data.base_hourly_rate).toFixed(2));
            $('#view_markup').text(markupPercent + '%');
            $('#view_st_rate').text('$' + parseFloat(data.straight_time_rate).toFixed(2));
            $('#view_ot_rate').text('$' + parseFloat(data.overtime_rate).toFixed(2));
            $('#view_dt_rate').text('$' + parseFloat(data.double_time_rate).toFixed(2));
            $('#view_effective_date').text(data.effective_date);

            openViewModal();
        });
    }

    // Delete rate
    function deleteRate(id) {
        confirmDelete(`/projects/${projectId}/billable-rates/${id}`, table);
    }

    // Form submission
    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        submitForm('createForm', '{{ route("projects.billable-rates.store", $project) }}', 'POST', table, 'createModal');
    });

    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        submitForm('editForm', `/projects/${projectId}/billable-rates/${currentRateId}`, 'PUT', table, 'editModal');
    });

    // Initialize DataTable
    var table = $('#ratesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("projects.billable-rates.index", $project) }}',
            type: 'GET'
        },
        columns: [
            { data: 'craft_name', name: 'craft.name' },
            { data: 'employee_name', name: 'employee.first_name' },
            {
                data: 'base_hourly_rate',
                name: 'base_hourly_rate',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                },
                className: 'text-right'
            },
            {
                data: 'markup_percentage',
                name: 'markup_percentage',
                className: 'text-right'
            },
            {
                data: 'straight_time_rate',
                name: 'straight_time_rate',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                },
                className: 'text-right'
            },
            {
                data: 'overtime_rate',
                name: 'overtime_rate',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                },
                className: 'text-right'
            },
            { data: 'effective_date', name: 'effective_date' },
            {
                data: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="flex items-center justify-center gap-1">
                            <button type="button" onclick="viewRate(${data})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition" title="View">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </button>
                            <button type="button" onclick="editRate(${data})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-600 hover:bg-amber-50 hover:text-amber-700 transition" title="Edit">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"></path></svg>
                            </button>
                            <button type="button" onclick="deleteRate(${data})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-700 transition" title="Delete">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path></svg>
                            </button>
                        </div>
                    `;
                },
                className: 'text-center'
            }
        ],
        language: {
            emptyTable: "No billable rates found."
        }
    });
</script>
@endpush

@endsection
