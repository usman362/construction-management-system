@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header Section -->
        <div class="flex justify-between items-start mb-8 pb-6 border-b-2 border-gray-300">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">FORECAST REPORT</h1>
                <p class="text-lg text-gray-600 mt-2">{{ $project->client->name ?? 'N/A' }}</p>
                <p class="text-sm text-gray-500 mt-1">Project #: {{ $project->project_number ?? 'N/A' }}</p>
                <p class="text-sm text-gray-500">Including CO's 1-{{ count($changeOrders ?? []) }}</p>
                <p class="text-sm text-gray-500">Date: {{ now()->format('m/d/Y') }}</p>
            </div>
            <div class="text-right">
                <div class="bg-blue-50 p-6 rounded mb-4">
                    <h3 class="text-xs font-semibold text-gray-700 mb-4">ORIGINAL BUDGET</h3>
                    <div class="mb-4">
                        <p class="text-xs text-gray-600">ESTIMATE</p>
                        <p class="text-2xl font-bold text-gray-800">${{ number_format($project->estimate_amount ?? 0, 2) }}</p>
                    </div>
                    <div class="mb-4">
                        <p class="text-xs text-gray-600">BUDGET</p>
                        <p class="text-2xl font-bold text-gray-800">${{ number_format($originalBudgetTotals['budget'] ?? 0, 2) }}</p>
                    </div>
                    <div class="mb-4">
                        <p class="text-xs text-gray-600">PROFIT</p>
                        <p class="text-2xl font-bold text-green-600">${{ number_format(($project->estimate_amount ?? 0) - ($originalBudgetTotals['budget'] ?? 0), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">PROFIT %</p>
                        @php
                            $origProfitPercent = ($originalBudgetTotals['budget'] ?? 0) > 0
                                ? ((($project->estimate_amount ?? 0) - ($originalBudgetTotals['budget'] ?? 0)) / ($originalBudgetTotals['budget'] ?? 0)) * 100
                                : 0;
                        @endphp
                        <p class="text-2xl font-bold text-green-600">{{ number_format($origProfitPercent, 1) }}%</p>
                    </div>
                </div>
                <div class="bg-green-50 p-6 rounded">
                    <h3 class="text-xs font-semibold text-gray-700 mb-4">FORECAST BUDGET</h3>
                    <div class="mb-4">
                        <p class="text-xs text-gray-600">BUDGET</p>
                        <p class="text-2xl font-bold text-gray-800">${{ number_format($forecastTotals['budget'] ?? 0, 2) }}</p>
                    </div>
                    <div class="mb-4">
                        <p class="text-xs text-gray-600">PROFIT</p>
                        <p class="text-2xl font-bold text-green-600">${{ number_format(($project->estimate_amount ?? 0) - ($forecastTotals['budget'] ?? 0), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">PROFIT %</p>
                        @php
                            $forecastProfitPercent = ($forecastTotals['budget'] ?? 0) > 0
                                ? ((($project->estimate_amount ?? 0) - ($forecastTotals['budget'] ?? 0)) / ($forecastTotals['budget'] ?? 0)) * 100
                                : 0;
                        @endphp
                        <p class="text-2xl font-bold text-green-600">{{ number_format($forecastProfitPercent, 1) }}%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Committed Cost to Date Section -->
        <div class="mb-10">
            <h2 class="text-xl font-bold text-gray-800 mb-4 bg-blue-100 p-3 rounded">PROJECT COMMITTED COST TO DATE</h2>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-blue-100 border border-gray-300">
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold">COST TYPE</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Forecast Budget</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Committed Cost TO DATE</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Invoiced</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Balance</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">% COMPLETE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $rowClass = 0;
                            $forecastGrandBudget = 0;
                            $forecastGrandCommitted = 0;
                            $forecastGrandInvoiced = 0;
                            $forecastGrandBalance = 0;
                        @endphp
                        @foreach($costData as $item)
                            @php
                                $forecastBudget = $item['forecast_budget'] ?? $item['budget'] ?? 0;
                                $forecastGrandBudget += $forecastBudget;
                                $forecastGrandCommitted += $item['committed'] ?? 0;
                                $forecastGrandInvoiced += $item['invoiced'] ?? 0;
                                $balance = $forecastBudget - ($item['committed'] ?? 0);
                                $forecastGrandBalance += $balance;
                                $pctComplete = $forecastBudget > 0 ? (($item['committed'] ?? 0) / $forecastBudget) * 100 : 0;
                                $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                                $rowClass++;
                            @endphp
                            <tr class="{{ $bgClass }} border border-gray-300 {{ $item['is_header'] ?? false ? 'font-bold bg-blue-50' : '' }}">
                                <td class="border border-gray-300 px-4 py-2 {{ $item['indent'] ?? false ? 'pl-8' : '' }}">{{ $item['code'] ?? 'N/A' }} - {{ $item['name'] ?? 'N/A' }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($forecastBudget, 2) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($item['committed'] ?? 0, 2) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($item['invoiced'] ?? 0, 2) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($balance, 2) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($pctComplete, 1) }}%</td>
                            </tr>
                        @endforeach
                        <tr class="bg-white border border-gray-300 h-2"></tr>
                        <tr class="bg-blue-100 border border-gray-300 font-bold">
                            <td class="border border-gray-300 px-4 py-2">FORECAST GRAND TOTAL</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($forecastGrandBudget, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($forecastGrandCommitted, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($forecastGrandInvoiced, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($forecastGrandBalance, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">
                                @php
                                    $grandPctComplete = $forecastGrandBudget > 0 ? ($forecastGrandCommitted / $forecastGrandBudget) * 100 : 0;
                                @endphp
                                {{ number_format($grandPctComplete, 1) }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Approved Change Orders Section -->
        @if(!empty($changeOrders) && count($changeOrders) > 0)
        <div class="mb-10">
            <h2 class="text-xl font-bold text-gray-800 mb-4 bg-blue-100 p-3 rounded">APPROVED CHANGE ORDERS - COST</h2>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-blue-100 border border-gray-300">
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold">CO Name</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Amount</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Committed Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $coTotalAmount = 0;
                            $coTotalCommitted = 0;
                            $rowClass = 0;
                        @endphp
                        @foreach($changeOrders as $co)
                            @php
                                $coTotalAmount += $co['amount'] ?? 0;
                                $coTotalCommitted += $co['committed'] ?? 0;
                                $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                                $rowClass++;
                            @endphp
                            <tr class="{{ $bgClass }} border border-gray-300">
                                <td class="border border-gray-300 px-4 py-2">{{ $co['name'] ?? 'N/A' }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($co['amount'] ?? 0, 2) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($co['committed'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-blue-100 border border-gray-300 font-bold">
                            <td class="border border-gray-300 px-4 py-2">GRAND TOTAL</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($forecastGrandBudget + $coTotalAmount, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($forecastGrandCommitted + $coTotalCommitted, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Manhour Data Section -->
        @if(!empty($manhourData) && count($manhourData) > 0)
        <div class="mb-10">
            <h2 class="text-xl font-bold text-gray-800 mb-4 bg-blue-100 p-3 rounded">MAN HOUR DATA</h2>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-blue-100 border border-gray-300">
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold">Code</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Actual Hours to-Date</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Forecast Hours</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Remain Mhrs</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">% of Remaining hours</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">LABOR COST TO DATE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalActualHours = 0;
                            $totalForecastHours = 0;
                            $totalCost = 0;
                            $rowClass = 0;
                        @endphp
                        @foreach($manhourData as $mh)
                            @php
                                $totalActualHours += $mh['actual_hours'] ?? 0;
                                $forecastHours = $mh['forecast_hours'] ?? $mh['budget_hours'] ?? 0;
                                $totalForecastHours += $forecastHours;
                                $totalCost += $mh['labor_cost'] ?? 0;
                                $remainingHours = $forecastHours - ($mh['actual_hours'] ?? 0);
                                $pctRemaining = $forecastHours > 0 ? ($remainingHours / $forecastHours) * 100 : 0;
                                $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                                $rowClass++;
                            @endphp
                            <tr class="{{ $bgClass }} border border-gray-300">
                                <td class="border border-gray-300 px-4 py-2">{{ $mh['code'] ?? 'N/A' }} - {{ $mh['name'] ?? 'N/A' }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($mh['actual_hours'] ?? 0, 1) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($forecastHours, 1) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($remainingHours, 1) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($pctRemaining, 1) }}%</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($mh['labor_cost'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-blue-100 border border-gray-300 font-bold">
                            <td class="border border-gray-300 px-4 py-2">TOTALS</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalActualHours, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalForecastHours, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalForecastHours - $totalActualHours, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">
                                @php
                                    $totalPctRemaining = $totalForecastHours > 0 ? (($totalForecastHours - $totalActualHours) / $totalForecastHours) * 100 : 0;
                                @endphp
                                {{ number_format($totalPctRemaining, 1) }}%
                            </td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalCost, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Manhours Summary Section -->
        <div class="mb-10">
            <h2 class="text-xl font-bold text-gray-800 mb-4 bg-blue-100 p-3 rounded">MANHOURS SUMMARY</h2>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-blue-100 border border-gray-300">
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold">Manhours</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Earned</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Productivity</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Forecast</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $summaryData = $forecastTotals ?? [];
                            $earned = $summaryData['earned'] ?? 0;
                            $productivity = $summaryData['productivity'] ?? 0;
                            $forecast = $summaryData['forecast'] ?? 0;
                            $variance = $forecast - $earned;
                        @endphp
                        <tr class="bg-gray-50 border border-gray-300">
                            <td class="border border-gray-300 px-4 py-2">{{ number_format($summaryData['total_hours'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($earned, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($productivity, 1) }}%</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($forecast, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right {{ $variance < 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold' }}">{{ number_format($variance, 1) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-8 flex gap-4">
            <a href="{{ route('reports.forecast', ['project' => $project->id]) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                Refresh
            </a>
            <a href="{{ route('reports.forecast.pdf', ['project' => $project->id]) }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">
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
