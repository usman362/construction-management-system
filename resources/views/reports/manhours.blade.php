@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Manhour Report - {{ $project->name ?? 'N/A' }}</h1>

        <!-- Filters -->
        <div class="bg-gray-50 p-6 rounded-lg mb-8 border border-gray-200">
            <form method="GET" action="{{ route('projects.reports.manhours', $project) }}" class="flex items-end gap-6">
                <!-- Date Range -->
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date Range</label>
                    <div class="flex gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Group By -->
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Group By</label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="group_by" value="employee" {{ ($groupBy ?? 'employee') === 'employee' ? 'checked' : '' }} class="w-4 h-4">
                            <span class="ml-2 text-sm text-gray-700">Employee</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="group_by" value="craft" {{ ($groupBy ?? 'employee') === 'craft' ? 'checked' : '' }} class="w-4 h-4">
                            <span class="ml-2 text-sm text-gray-700">Craft</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="group_by" value="cost_code" {{ ($groupBy ?? 'employee') === 'cost_code' ? 'checked' : '' }} class="w-4 h-4">
                            <span class="ml-2 text-sm text-gray-700">Cost Code</span>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Filter
                </button>
            </form>
        </div>

        <!-- Table: By Employee -->
        @if(($groupBy ?? 'employee') === 'employee')
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-blue-100 border border-gray-300">
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Employee Name</th>
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Craft</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Regular Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">OT Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">DT Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Total Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Labor Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalRegular = 0;
                        $totalOT = 0;
                        $totalDT = 0;
                        $totalHours = 0;
                        $totalCost = 0;
                        $rowClass = 0;
                    @endphp
                    @foreach($manhourData as $item)
                        @php
                            $totalRegular += $item['regular_hours'] ?? 0;
                            $totalOT += $item['ot_hours'] ?? 0;
                            $totalDT += $item['dt_hours'] ?? 0;
                            $total = ($item['regular_hours'] ?? 0) + ($item['ot_hours'] ?? 0) + ($item['dt_hours'] ?? 0);
                            $totalHours += $total;
                            $totalCost += $item['labor_cost'] ?? 0;
                            $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                            $rowClass++;
                        @endphp
                        <tr class="{{ $bgClass }} border border-gray-300">
                            <td class="border border-gray-300 px-4 py-2">{{ $item['employee_name'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $item['craft'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['regular_hours'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['ot_hours'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['dt_hours'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right font-semibold">{{ number_format($total, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($item['labor_cost'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-blue-100 border border-gray-300 font-bold">
                        <td colspan="2" class="border border-gray-300 px-4 py-2">TOTALS</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalRegular, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalOT, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalDT, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalHours, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalCost, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table: By Craft -->
        @elseif(($groupBy ?? 'employee') === 'craft')
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-blue-100 border border-gray-300">
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Craft Code</th>
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Craft Name</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Employees Count</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Total Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Avg Hrs/Employee</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalEmployees = 0;
                        $totalHours = 0;
                        $totalCost = 0;
                        $rowClass = 0;
                    @endphp
                    @foreach($manhourData as $item)
                        @php
                            $totalEmployees += $item['employee_count'] ?? 0;
                            $totalHours += $item['total_hours'] ?? 0;
                            $totalCost += $item['total_cost'] ?? 0;
                            $avgHrs = ($item['employee_count'] ?? 0) > 0 ? ($item['total_hours'] ?? 0) / ($item['employee_count'] ?? 0) : 0;
                            $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                            $rowClass++;
                        @endphp
                        <tr class="{{ $bgClass }} border border-gray-300">
                            <td class="border border-gray-300 px-4 py-2">{{ $item['craft_code'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $item['craft_name'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ $item['employee_count'] ?? 0 }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['total_hours'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($avgHrs, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($item['total_cost'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-blue-100 border border-gray-300 font-bold">
                        <td colspan="2" class="border border-gray-300 px-4 py-2">TOTALS</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ $totalEmployees }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalHours, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalEmployees > 0 ? $totalHours / $totalEmployees : 0, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalCost, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table: By Cost Code -->
        @else
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-blue-100 border border-gray-300">
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Cost Code</th>
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Name</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Budget Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Actual Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Variance</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">% Used</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalBudgetHours = 0;
                        $totalActualHours = 0;
                        $rowClass = 0;
                    @endphp
                    @foreach($manhourData as $item)
                        @php
                            $totalBudgetHours += $item['budget_hours'] ?? 0;
                            $totalActualHours += $item['actual_hours'] ?? 0;
                            $variance = ($item['actual_hours'] ?? 0) - ($item['budget_hours'] ?? 0);
                            $pctUsed = ($item['budget_hours'] ?? 0) > 0 ? (($item['actual_hours'] ?? 0) / ($item['budget_hours'] ?? 0)) * 100 : 0;
                            $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                            $rowClass++;
                        @endphp
                        <tr class="{{ $bgClass }} border border-gray-300">
                            <td class="border border-gray-300 px-4 py-2">{{ $item['cost_code'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $item['name'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['budget_hours'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['actual_hours'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right {{ $variance < 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">{{ number_format($variance, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($pctUsed, 1) }}%</td>
                        </tr>
                    @endforeach
                    <tr class="bg-blue-100 border border-gray-300 font-bold">
                        <td colspan="2" class="border border-gray-300 px-4 py-2">TOTALS</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalBudgetHours, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalActualHours, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalActualHours - $totalBudgetHours, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalBudgetHours > 0 ? ($totalActualHours / $totalBudgetHours) * 100 : 0, 1) }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- Actions -->
        <div class="mt-8 flex gap-4">
            <a href="{{ route('projects.reports.manhours.pdf', $project) }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">
                Download PDF
            </a>
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                Print
            </button>
            <a href="{{ route('projects.show', $project->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                Back to Project
            </a>
        </div>
    </div>
</div>
@endsection
