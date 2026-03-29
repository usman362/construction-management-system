@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Timesheet Report</h1>

        <!-- Filters -->
        <div class="bg-gray-50 p-6 rounded-lg mb-8 border border-gray-200">
            <form method="GET" action="{{ route('reports.timesheets') }}" class="grid grid-cols-2 gap-6">
                <!-- Employee Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Employee</label>
                    <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Employees</option>
                        @foreach($employees ?? [] as $emp)
                            <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Project Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Project</label>
                    <select name="project_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Projects</option>
                        @foreach($projects ?? [] as $proj)
                            <option value="{{ $proj->id }}" {{ request('project_id') == $proj->id ? 'selected' : '' }}>
                                {{ $proj->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range -->
                <div class="col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date Range</label>
                    <div class="flex gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Group By -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Group By</label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="group_by" value="employee" {{ (request('group_by') ?? 'employee') === 'employee' ? 'checked' : '' }} class="w-4 h-4">
                            <span class="ml-2 text-sm">Employee</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="group_by" value="project" {{ (request('group_by') ?? 'employee') === 'project' ? 'checked' : '' }} class="w-4 h-4">
                            <span class="ml-2 text-sm">Project</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="group_by" value="date" {{ (request('group_by') ?? 'employee') === 'date' ? 'checked' : '' }} class="w-4 h-4">
                            <span class="ml-2 text-sm">Date</span>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Total Hours</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">{{ number_format($summary['total_hours'] ?? 0, 1) }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border border-green-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Total Cost</p>
                <p class="text-3xl font-bold text-green-600 mt-2">${{ number_format($summary['total_cost'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg border border-purple-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Billable</p>
                <p class="text-3xl font-bold text-purple-600 mt-2">${{ number_format($summary['total_billable'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-lg border border-orange-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Avg Hours/Day</p>
                <p class="text-3xl font-bold text-orange-600 mt-2">{{ number_format($summary['avg_hours_per_day'] ?? 0, 1) }}</p>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-blue-100 border border-gray-300">
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Group</th>
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Detail</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Hours</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Labor Cost</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Billable</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalHours = 0;
                        $totalCost = 0;
                        $totalBillable = 0;
                        $rowClass = 0;
                        $currentGroup = null;
                    @endphp
                    @foreach($groupedData as $item)
                        @php
                            $totalHours += $item['hours'] ?? 0;
                            $totalCost += $item['labor_cost'] ?? 0;
                            $totalBillable += $item['billable_amount'] ?? 0;
                            $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                            $rowClass++;
                        @endphp
                        <tr class="{{ $bgClass }} border border-gray-300">
                            <td class="border border-gray-300 px-4 py-2 font-semibold">{{ $item['group_name'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $item['detail'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['hours'] ?? 0, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($item['labor_cost'] ?? 0, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($item['billable_amount'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-blue-100 border border-gray-300 font-bold">
                        <td colspan="2" class="border border-gray-300 px-4 py-2">TOTALS</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalHours, 2) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalCost, 2) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalBillable, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Actions -->
        <div class="mt-8 flex gap-4">
            <a href="{{ route('reports.timesheets.pdf', request()->query()) }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">
                Download PDF
            </a>
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                Print
            </button>
            <a href="{{ route('dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
