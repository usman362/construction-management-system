@extends('layouts.app')

@section('title', $project->name . ' - ' . $project->project_number)

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'overview' }">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $project->name }}</h1>
            <p class="text-gray-600 mt-1">Project #{{ $project->project_number }}</p>
        </div>
        <div class="flex gap-2">
            <button onclick="editProject()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                Edit
            </button>
        </div>
    </div>

    <!-- Project Header Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-600">Client</p>
                <p class="text-lg font-semibold text-gray-900">{{ $project->client?->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Status</p>
                @php
                    $statusClasses = [
                        'active' => 'bg-green-100 text-green-800',
                        'on_hold' => 'bg-yellow-100 text-yellow-800',
                        'completed' => 'bg-blue-100 text-blue-800',
                        'closed' => 'bg-gray-100 text-gray-800',
                        'bidding' => 'bg-purple-100 text-purple-800',
                        'awarded' => 'bg-orange-100 text-orange-800',
                    ];
                @endphp
                <span class="inline-block mt-1 px-3 py-1 rounded-full text-sm font-medium {{ $statusClasses[$project->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucwords(str_replace('_', ' ', $project->status)) }}
                </span>
            </div>
            <div>
                <p class="text-sm text-gray-600">Start Date</p>
                <p class="text-lg font-semibold text-gray-900">{{ $project->start_date?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">End Date</p>
                <p class="text-lg font-semibold text-gray-900">{{ $project->end_date?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t">
            <p class="text-sm text-gray-600 mb-2">Location</p>
            <p class="text-gray-900">{{ $project->address }}, {{ $project->city }}, {{ $project->state }} {{ $project->zip }}</p>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Original Budget</p>
            <p class="text-2xl font-bold text-gray-900">${{ number_format($project->original_budget, 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Current Budget</p>
            <p class="text-2xl font-bold text-gray-900">${{ number_format($project->current_budget ?? 0, 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Committed</p>
            <p class="text-2xl font-bold text-gray-900">${{ number_format($committedTotal ?? 0, 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">Profit</p>
            @php
                $profit = ($project->contract_value ?? 0) - ($committedTotal ?? 0);
                $profitClass = $profit >= 0 ? 'text-green-600' : 'text-red-600';
            @endphp
            <p class="text-2xl font-bold {{ $profitClass }}">${{ number_format($profit, 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600">% Complete</p>
            <p class="text-2xl font-bold text-gray-900">{{ $percentComplete ?? 0 }}%</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-md">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 flex flex-wrap">
            <button
                @click="activeTab = 'overview'"
                :class="{ 'border-b-2 border-blue-600 text-blue-600': activeTab === 'overview', 'text-gray-700 hover:text-gray-900': activeTab !== 'overview' }"
                class="px-6 py-4 font-medium transition"
            >
                Overview
            </button>
            <button
                @click="activeTab = 'budget'"
                :class="{ 'border-b-2 border-blue-600 text-blue-600': activeTab === 'budget', 'text-gray-700 hover:text-gray-900': activeTab !== 'budget' }"
                class="px-6 py-4 font-medium transition"
            >
                Budget
            </button>
            <button
                @click="activeTab = 'change-orders'"
                :class="{ 'border-b-2 border-blue-600 text-blue-600': activeTab === 'change-orders', 'text-gray-700 hover:text-gray-900': activeTab !== 'change-orders' }"
                class="px-6 py-4 font-medium transition"
            >
                Change Orders
            </button>
            <button
                @click="activeTab = 'timesheets'"
                :class="{ 'border-b-2 border-blue-600 text-blue-600': activeTab === 'timesheets', 'text-gray-700 hover:text-gray-900': activeTab !== 'timesheets' }"
                class="px-6 py-4 font-medium transition"
            >
                Timesheets
            </button>
            <button
                @click="activeTab = 'costs'"
                :class="{ 'border-b-2 border-blue-600 text-blue-600': activeTab === 'costs', 'text-gray-700 hover:text-gray-900': activeTab !== 'costs' }"
                class="px-6 py-4 font-medium transition"
            >
                Committed Costs
            </button>
            <button
                @click="activeTab = 'client-billing'"
                :class="{ 'border-b-2 border-blue-600 text-blue-600': activeTab === 'client-billing', 'text-gray-700 hover:text-gray-900': activeTab !== 'client-billing' }"
                class="px-6 py-4 font-medium transition"
            >
                Client Billing
            </button>
            <button
                @click="activeTab = 'reports'"
                :class="{ 'border-b-2 border-blue-600 text-blue-600': activeTab === 'reports', 'text-gray-700 hover:text-gray-900': activeTab !== 'reports' }"
                class="px-6 py-4 font-medium transition"
            >
                Reports
            </button>
        </div>

        {{-- Quick Links Bar --}}
        <div class="flex flex-wrap gap-2 px-6 py-3 bg-gray-50 border-b border-gray-200">
            <a href="{{ route('projects.budget.index', $project) }}" class="text-sm px-3 py-1.5 bg-white border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition text-gray-700">Budget Lines</a>
            <a href="{{ route('projects.change-orders.index', $project) }}" class="text-sm px-3 py-1.5 bg-white border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition text-gray-700">Change Orders</a>
            <a href="{{ route('projects.estimates.index', $project) }}" class="text-sm px-3 py-1.5 bg-white border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition text-gray-700">Estimates</a>
            <a href="{{ route('projects.commitments.index', $project) }}" class="text-sm px-3 py-1.5 bg-white border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition text-gray-700">Commitments</a>
            <a href="{{ route('purchase-orders.index', ['project_id' => $project->id]) }}" class="text-sm px-3 py-1.5 bg-white border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition text-gray-700">Purchase Orders</a>
            <a href="{{ route('projects.manhour-budgets.index', $project) }}" class="text-sm px-3 py-1.5 bg-white border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition text-gray-700">Manhour Budgets</a>
            <a href="{{ route('projects.billable-rates.index', $project) }}" class="text-sm px-3 py-1.5 bg-white border border-gray-300 rounded-lg hover:bg-green-50 hover:border-green-300 hover:text-green-700 transition text-gray-700 font-medium">Billable Rates</a>
            <a href="{{ route('projects.daily-logs.index', $project) }}" class="text-sm px-3 py-1.5 bg-white border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition text-gray-700">Daily Logs</a>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" class="space-y-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                    <p class="text-gray-700">{{ $project->description ?? 'No description provided.' }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Dates</h3>
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Start:</dt>
                                <dd class="text-gray-900">{{ $project->start_date?->format('M d, Y') ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">End:</dt>
                                <dd class="text-gray-900">{{ $project->end_date?->format('M d, Y') ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Substantial Completion:</dt>
                                <dd class="text-gray-900">{{ $project->substantial_completion_date?->format('M d, Y') ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Contract Info</h3>
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">PO Number:</dt>
                                <dd class="text-gray-900">{{ $project->po_number ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">PO Date:</dt>
                                <dd class="text-gray-900">{{ $project->po_date?->format('M d, Y') ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Contract Value:</dt>
                                <dd class="text-gray-900 font-semibold">${{ number_format($project->contract_value, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Budget Tab -->
            <div x-show="activeTab === 'budget'" class="space-y-4">
                <div class="flex justify-end mb-4">
                    <button type="button" onclick="document.getElementById('addBudgetModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Budget Line
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Phase Code</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Cost Type</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Budget</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Revised</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Manhours</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Committed</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Invoiced</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Balance</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">% Complete</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($budgetLines as $line)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-gray-900 font-mono text-xs">{{ $line->costCode?->code ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-900">{{ $line->costType?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-900">{{ $line->description ?? '—' }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900">${{ number_format($line->budget_amount ?? 0, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900">${{ number_format($line->revised_amount ?? 0, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900">{{ number_format($line->labor_hours ?? 0, 2) }} hrs</td>
                                    <td class="px-4 py-2 text-right text-gray-900">${{ number_format($line->committed ?? 0, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900">${{ number_format($line->invoiced ?? 0, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900">${{ number_format(($line->revised_amount ?? 0) - ($line->committed ?? 0), 2) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900">{{ $line->percent_complete ?? 0 }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-4 text-center text-gray-500">No budget lines.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Add Budget Line Modal -->
                <div id="addBudgetModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.5)" onclick="if(event.target===this)this.classList.add('hidden')">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900">Add Budget Line</h3>
                            <button type="button" onclick="document.getElementById('addBudgetModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form id="addBudgetForm" class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phase Code</label>
                                <select name="cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">— None —</option>
                                    @foreach($allCostCodes ?? [] as $cc)
                                        <option value="{{ $cc->id }}">{{ $cc->code }} — {{ $cc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cost Type</label>
                                <select name="cost_type_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">— None —</option>
                                    @foreach($allCostTypes ?? [] as $ct)
                                        <option value="{{ $ct->id }}">{{ $ct->code }} — {{ $ct->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                                <input type="text" name="description" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                                <input type="number" step="0.01" name="budget_amount" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Labor Hours (Manhours)</label>
                                <input type="number" step="0.5" min="0" name="labor_hours" placeholder="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </form>
                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                            <button type="button" onclick="document.getElementById('addBudgetModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                            <button type="button" onclick="submitBudgetLine()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Budget Line</button>
                        </div>
                    </div>
                </div>
                <script>
                function submitBudgetLine() {
                    var form = document.getElementById('addBudgetForm');
                    if (!form.reportValidity()) return;
                    $.ajax({
                        url: '{{ route("projects.budget.store", $project) }}',
                        type: 'POST',
                        data: $(form).serialize(),
                        success: function(res) {
                            Toast.fire({icon:'success', title: res.message || 'Budget line added'});
                            setTimeout(() => window.location.reload(), 600);
                        },
                        error: function(xhr) {
                            var msg = xhr.responseJSON?.message || 'Could not save budget line';
                            if (xhr.responseJSON?.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                            }
                            Toast.fire({icon:'error', title: msg});
                        }
                    });
                }
                </script>
            </div>

            <!-- Change Orders Tab -->
            <div x-show="activeTab === 'change-orders'" class="space-y-4">
                @php
                    $coApproved = $changeOrders->where('status', 'approved');
                    $coPending = $changeOrders->where('status', 'pending');
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-sm font-medium text-green-800">Total approved</p>
                        <p class="text-2xl font-bold text-green-900">${{ number_format((float) $coApproved->sum('amount'), 2) }}</p>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <p class="text-sm font-medium text-amber-800">Total pending</p>
                        <p class="text-2xl font-bold text-amber-900">${{ number_format((float) $coPending->sum('amount'), 2) }}</p>
                    </div>
                </div>
                <div class="flex justify-end mb-4">
                    <a href="{{ route('projects.change-orders.index', $project) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        New Change Order
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Number</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Date</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Description</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Amount</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($changeOrders as $co)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-gray-900 font-medium">{{ $co->co_number ?? $co->id }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $co->date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $co->description ?? 'N/A' }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900">${{ number_format($co->amount ?? 0, 2) }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ ucfirst($co->status ?? 'pending') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <a href="{{ route('projects.change-orders.show', [$project, $co]) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition" title="View">
                                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-gray-500">No change orders.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Timesheets Tab -->
            <div x-show="activeTab === 'timesheets'" class="space-y-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Date</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Employee</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Hours</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Cost</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($timesheets as $timesheet)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-gray-900">{{ $timesheet->date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td class="px-4 py-2 text-gray-900">{{ $timesheet->employee?->full_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900">{{ number_format((float) ($timesheet->total_hours ?? 0), 2) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900">${{ number_format((float) ($timesheet->total_cost ?? 0), 2) }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                            {{ ucfirst($timesheet->status ?? 'submitted') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">No timesheets.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Committed Costs Tab (PO-style columns per client) -->
            <div x-show="activeTab === 'costs'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Purchase Orders</h3>
                    <a href="{{ route('purchase-orders.index', ['project_id' => $project->id]) }}" class="text-sm text-blue-600 hover:text-blue-800">Manage Purchase Orders →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">PO #</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Vendor</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Phase Code</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Cost Type</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Description</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Date</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700">Amount</th>
                                <th class="px-3 py-2 text-center font-semibold text-gray-700">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($purchaseOrders ?? [] as $po)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-gray-900 font-mono text-xs">
                                        {{ $po->po_number }}
                                        @if($po->parent) <span class="text-gray-400 text-xs">(CO of {{ $po->parent->po_number }})</span> @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-900">{{ $po->vendor?->name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-700 font-mono text-xs">{{ $po->costCode?->code ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $po->costType?->name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ \Illuminate\Support\Str::limit($po->description, 60) }}</td>
                                    <td class="px-3 py-2 text-gray-600 text-xs">{{ optional($po->date)->format('M d, Y') }}</td>
                                    <td class="px-3 py-2 text-right text-gray-900 font-medium">${{ number_format($po->total_amount ?? 0, 2) }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                            @if($po->status === 'received') bg-green-100 text-green-700
                                            @elseif($po->status === 'partial') bg-amber-100 text-amber-700
                                            @elseif($po->status === 'issued') bg-blue-100 text-blue-700
                                            @elseif($po->status === 'cancelled') bg-red-100 text-red-700
                                            @else bg-gray-100 text-gray-700 @endif">{{ ucfirst($po->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-3 py-4 text-center text-gray-500">No purchase orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pt-4">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Committed Costs Summary (by Phase Code)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Phase Code</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Name</th>
                                    <th class="px-3 py-2 text-right font-semibold text-gray-700">Total Committed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($costSummary as $cost)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-900 font-mono text-xs">{{ $cost->code ?? '—' }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $cost->description ?? '—' }}</td>
                                        <td class="px-3 py-2 text-right text-gray-900 font-medium">${{ number_format($cost->total ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-3 py-4 text-center text-gray-500">No cost data available.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Client Billing Tab -->
            <div x-show="activeTab === 'client-billing'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Client Billing</h3>
                        <p class="text-xs text-gray-500">Invoices sent to the client for this project.</p>
                    </div>
                    @if(Route::has('billing-invoices.create'))
                        <a href="{{ route('billing-invoices.create', ['project_id' => $project->id]) }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            New Invoice
                        </a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Invoice #</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Date</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Due</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700">Amount</th>
                                <th class="px-3 py-2 text-center font-semibold text-gray-700">Status</th>
                                <th class="px-3 py-2 text-center font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($billingInvoices ?? [] as $inv)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $inv->invoice_number ?? $inv->number ?? ('#' . $inv->id) }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ optional($inv->invoice_date ?? $inv->date)->format('M d, Y') }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ optional($inv->due_date)->format('M d, Y') }}</td>
                                    <td class="px-3 py-2 text-right text-gray-900 font-medium">${{ number_format($inv->total_amount ?? $inv->amount ?? 0, 2) }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                            @if(in_array($inv->status ?? '', ['paid'])) bg-green-100 text-green-700
                                            @elseif(in_array($inv->status ?? '', ['sent','issued'])) bg-blue-100 text-blue-700
                                            @elseif(in_array($inv->status ?? '', ['overdue'])) bg-red-100 text-red-700
                                            @else bg-gray-100 text-gray-700 @endif">{{ ucfirst($inv->status ?? 'draft') }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if(Route::has('billing-invoices.show'))
                                            <a href="{{ route('billing-invoices.show', $inv) }}" class="text-blue-600 hover:text-blue-800 text-xs">View</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">No invoices billed to the client yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reports Tab -->
            <div x-show="activeTab === 'reports'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('projects.reports.cost-report', $project) }}" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition">
                        <h3 class="font-semibold text-blue-900">Cost Report</h3>
                        <p class="text-sm text-blue-700 mt-1">View detailed cost analysis</p>
                    </a>
                    <a href="{{ route('projects.reports.forecast', $project) }}" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition">
                        <h3 class="font-semibold text-blue-900">Forecast</h3>
                        <p class="text-sm text-blue-700 mt-1">Project forecast and projections</p>
                    </a>
                    <a href="{{ route('projects.reports.manhours', $project) }}" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition">
                        <h3 class="font-semibold text-blue-900">Manhours</h3>
                        <p class="text-sm text-blue-700 mt-1">Labor hours and productivity</p>
                    </a>
                    <a href="{{ route('projects.reports.profit-loss', $project) }}" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition">
                        <h3 class="font-semibold text-blue-900">P&L</h3>
                        <p class="text-sm text-blue-700 mt-1">Profit and loss statement</p>
                    </a>
                    <a href="{{ route('projects.reports.productivity', $project) }}" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition">
                        <h3 class="font-semibold text-blue-900">Productivity</h3>
                        <p class="text-sm text-blue-700 mt-1">Productivity metrics and analysis</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('editModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Edit Project</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Project Number *</label><input type="text" name="project_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Name *</label><input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Client *</label><select name="client_id" id="editClientId" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></select></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label><input type="date" name="start_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label><input type="date" name="end_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Budget *</label><input type="number" step="0.01" name="budget" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Contract Value</label><input type="number" step="0.01" name="contract_value" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="0.00"></div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Client PO #</label><input type="text" name="po_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">PO Date</label><input type="date" name="po_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Status *</label><select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    @foreach(['bidding' => 'Bidding', 'awarded' => 'Awarded', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'closed' => 'Closed'] as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea></div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button id="editSaveBtn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function editProject() {
    $.get(window.BASE_URL+'/projects/{{ $project->id }}/edit', function(d) {
        var p = d.project || d;
        var f = document.getElementById('editForm');
        f.querySelector('[name="project_number"]').value = p.project_number || '';
        f.querySelector('[name="name"]').value = p.name || '';
        f.querySelector('[name="start_date"]').value = p.start_date ? String(p.start_date).substring(0,10) : '';
        f.querySelector('[name="end_date"]').value = p.end_date ? String(p.end_date).substring(0,10) : '';
        f.querySelector('[name="budget"]').value = p.current_budget != null ? p.current_budget : (p.budget != null ? p.budget : '');
        f.querySelector('[name="contract_value"]').value = p.contract_value || '';
        f.querySelector('[name="po_number"]').value = p.po_number || '';
        f.querySelector('[name="po_date"]').value = p.po_date ? String(p.po_date).substring(0,10) : '';
        f.querySelector('[name="status"]').value = p.status || 'active';
        f.querySelector('[name="description"]').value = p.description || '';

        var opts = '<option value="">Select Client</option>';
        if (d.clients) {
            d.clients.forEach(function(c) {
                opts += '<option value="'+c.id+'" '+(c.id == p.client_id ? 'selected' : '')+'>'+c.name+'</option>';
            });
        }
        document.getElementById('editClientId').innerHTML = opts;

        document.getElementById('editSaveBtn').onclick = function() {
            submitForm('editForm', window.BASE_URL+'/projects/{{ $project->id }}', 'PUT', null, 'editModal');
        };
        openModal('editModal');
    });
}
</script>
@endpush

{{-- Project Documents Section --}}
@include('documents.partials.upload-panel', [
    'documentableType' => get_class($project),
    'documentableId'   => $project->id,
    'documents'        => $project->documents,
])

@endsection
