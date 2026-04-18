@php
    $e = $employee ?? null;
    $val = function($field, $default = '') use ($e) {
        return old($field, $e?->{$field} ?? $default);
    };
    $check = function($field, $default = false) use ($e) {
        if (old($field) !== null) return (bool) old($field);
        return $e ? (bool) $e->{$field} : $default;
    };
@endphp

<!-- ─── Identification ─────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow-md p-6 space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 border-b pb-2">Identification</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee Number *</label>
            <input type="text" name="employee_number" value="{{ $val('employee_number') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('employee_number') border-red-500 @enderror">
            @error('employee_number')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Legacy Employee ID</label>
            <input type="text" name="legacy_employee_id" value="{{ $val('legacy_employee_id') }}" placeholder="e.g. JEG2723"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'terminated' => 'Terminated'] as $v => $l)
                    <option value="{{ $v }}" @selected($val('status', 'active') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<!-- ─── Personal Information ───────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow-md p-6 space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 border-b pb-2">Personal Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
            <input type="text" name="first_name" value="{{ $val('first_name') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('first_name') border-red-500 @enderror">
            @error('first_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
            <input type="text" name="middle_name" value="{{ $val('middle_name') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
            <input type="text" name="last_name" value="{{ $val('last_name') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('last_name') border-red-500 @enderror">
            @error('last_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ $val('email') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror">
            @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Primary Phone</label>
            <input type="tel" name="phone" value="{{ $val('phone') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Home Phone</label>
            <input type="tel" name="home_phone" value="{{ $val('home_phone') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Work Cell</label>
            <input type="tel" name="work_cell" value="{{ $val('work_cell') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Personal Cell</label>
            <input type="tel" name="personal_cell" value="{{ $val('personal_cell') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
    </div>
</div>

<!-- ─── Address ────────────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow-md p-6 space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 border-b pb-2">Address</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Address 1</label>
            <input type="text" name="address_1" value="{{ $val('address_1') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Address 2</label>
            <input type="text" name="address_2" value="{{ $val('address_2') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
            <input type="text" name="city" value="{{ $val('city') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
            <input type="text" name="state" value="{{ $val('state') }}" maxlength="50"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP</label>
            <input type="text" name="zip" value="{{ $val('zip') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
    </div>
</div>

<!-- ─── Work & Craft ───────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow-md p-6 space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 border-b pb-2">Work & Craft</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Craft</label>
            <select name="craft_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Select Craft</option>
                @foreach($crafts as $craft)
                    <option value="{{ $craft->id }}" @selected((string) $val('craft_id') === (string) $craft->id)>{{ $craft->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">System Role *</label>
            <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @foreach([
                    'field' => 'Field',
                    'foreman' => 'Foreman',
                    'superintendent' => 'Superintendent',
                    'project_manager' => 'Project Manager',
                    'admin' => 'Admin',
                    'accounting' => 'Accounting',
                ] as $v => $l)
                    <option value="{{ $v }}" @selected($val('role', 'field') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee Type</label>
            <input type="text" name="employee_type" value="{{ $val('employee_type') }}" placeholder="e.g. Operator, Laborer"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Legacy Position</label>
            <input type="text" name="legacy_position" value="{{ $val('legacy_position') }}" placeholder="e.g. CROPER"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Legacy Craft</label>
            <input type="text" name="legacy_craft" value="{{ $val('legacy_craft') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <input type="text" name="department" value="{{ $val('department') }}" placeholder="e.g. CRANE"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Union</label>
            <input type="text" name="union" value="{{ $val('union') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Classification</label>
            <input type="text" name="classification" value="{{ $val('classification') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex items-center gap-6 pt-6">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="is_supervisor" value="1" {{ $check('is_supervisor') ? 'checked' : '' }} class="rounded border-gray-300">
                <span class="text-sm font-medium text-gray-700">Supervisor</span>
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="certified_pay" value="1" {{ $check('certified_pay') ? 'checked' : '' }} class="rounded border-gray-300">
                <span class="text-sm font-medium text-gray-700">Certified Pay</span>
            </label>
        </div>
    </div>
</div>

<!-- ─── Payroll & Rates ────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow-md p-6 space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 border-b pb-2">Payroll & Rates</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pay Cycle</label>
            <select name="pay_cycle" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @foreach(['weekly' => 'Weekly', 'bi_weekly' => 'Bi-weekly', 'semi_monthly' => 'Semi-monthly', 'monthly' => 'Monthly'] as $v => $l)
                    <option value="{{ $v }}" @selected($val('pay_cycle', 'weekly') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pay Type</label>
            <select name="pay_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @foreach(['hourly' => 'Hourly', 'salary' => 'Salary'] as $v => $l)
                    <option value="{{ $v }}" @selected($val('pay_type', 'hourly') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ST Burden ($/hr)</label>
            <input type="number" step="0.0001" name="st_burden_rate" value="{{ $val('st_burden_rate') }}" placeholder="0.0000"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            <p class="text-[11px] text-gray-500 mt-1">Pooled hourly burden on straight time (SUTA + FICA + WC + Benefits + Overhead).</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">OT Burden ($/hr)</label>
            <input type="number" step="0.0001" name="ot_burden_rate" value="{{ $val('ot_burden_rate') }}" placeholder="0.0000"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            <p class="text-[11px] text-gray-500 mt-1">Pooled hourly burden on overtime.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hourly Rate *</label>
            <input type="number" step="0.01" name="hourly_rate" value="{{ $val('hourly_rate', '0') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('hourly_rate') border-red-500 @enderror">
            @error('hourly_rate')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Overtime Rate *</label>
            <input type="number" step="0.01" name="overtime_rate" value="{{ $val('overtime_rate', '0') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('overtime_rate') border-red-500 @enderror">
            @error('overtime_rate')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">OT Billable Rate *</label>
            <input type="number" step="0.01" name="billable_rate" value="{{ $val('billable_rate', '0') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('billable_rate') border-red-500 @enderror">
            @error('billable_rate')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<!-- ─── Tax & Work Comp ────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow-md p-6 space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 border-b pb-2">Tax & Work Comp</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Work Comp Code</label>
            <input type="text" name="work_comp_code" value="{{ $val('work_comp_code') }}" placeholder="e.g. 9534AL"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SUTA State</label>
            <input type="text" name="suta_state" value="{{ $val('suta_state') }}" maxlength="10" placeholder="e.g. LA"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">State Tax</label>
            <input type="text" name="state_tax" value="{{ $val('state_tax') }}" maxlength="10"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">City Tax</label>
            <input type="text" name="city_tax" value="{{ $val('city_tax') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
    </div>
</div>

<!-- ─── Employment Dates ───────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow-md p-6 space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 border-b pb-2">Employment Dates</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hire Date *</label>
            <input type="date" name="hire_date" value="{{ $val('hire_date') ? (is_string($val('hire_date')) ? $val('hire_date') : optional($val('hire_date'))->format('Y-m-d')) : '' }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('hire_date') border-red-500 @enderror">
            @error('hire_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ $val('start_date') ? (is_string($val('start_date')) ? $val('start_date') : optional($val('start_date'))->format('Y-m-d')) : '' }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rehire Date</label>
            <input type="date" name="rehire_date" value="{{ $val('rehire_date') ? (is_string($val('rehire_date')) ? $val('rehire_date') : optional($val('rehire_date'))->format('Y-m-d')) : '' }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Termination Date</label>
            <input type="date" name="term_date" value="{{ $val('term_date') ? (is_string($val('term_date')) ? $val('term_date') : optional($val('term_date'))->format('Y-m-d')) : '' }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Termination Reason</label>
        <textarea name="term_reason" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ $val('term_reason') }}</textarea>
    </div>
</div>
