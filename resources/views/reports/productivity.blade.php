@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Productivity Report - {{ $project->name ?? 'N/A' }}</h1>

        <!-- Summary Cards -->
        <div class="grid grid-cols-3 gap-4 mb-10">
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Budget Hours</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">{{ number_format($summary['budget_hours'] ?? 0, 1) }}</p>
            </div>
            <div class="bg-orange-50 p-6 rounded-lg border border-orange-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Actual Hours</p>
                <p class="text-3xl font-bold text-orange-600 mt-2">{{ number_format($summary['actual_hours'] ?? 0, 1) }}</p>
            </div>
            <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Earned Hours</p>
                <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($summary['earned_hours'] ?? 0, 1) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-10">
            @php
                $productivity = ($summary['actual_hours'] ?? 0) > 0
                    ? (($summary['earned_hours'] ?? 0) / ($summary['actual_hours'] ?? 0)) * 100
                    : 0;
                $productivityColor = $productivity >= 100 ? 'green' : ($productivity >= 90 ? 'yellow' : 'red');
            @endphp
            <div class="bg-{{ $productivityColor }}-50 p-6 rounded-lg border border-{{ $productivityColor }}-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Productivity %</p>
                <p class="text-3xl font-bold text-{{ $productivityColor }}-600 mt-2">{{ number_format($productivity, 1) }}%</p>
            </div>
            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Forecast at Completion</p>
                <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($summary['forecast_at_completion'] ?? 0, 1) }}</p>
            </div>
            <div class="bg-red-50 p-6 rounded-lg border border-red-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Variance</p>
                @php
                    $variance = ($summary['forecast_at_completion'] ?? 0) - ($summary['budget_hours'] ?? 0);
                @endphp
                <p class="text-3xl font-bold {{ $variance < 0 ? 'text-green-600' : 'text-red-600' }} mt-2">{{ number_format($variance, 1) }}</p>
            </div>
        </div>

        <!-- Productivity Table -->
        <div class="overflow-x-auto mb-10">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-blue-100 border border-gray-300">
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Cost Code</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Budget Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Actual Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Earned Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Productivity %</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Forecast</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalBudget = 0;
                        $totalActual = 0;
                        $totalEarned = 0;
                        $rowClass = 0;
                    @endphp
                    @foreach($productivityData as $item)
                        @php
                            $totalBudget += $item['budget_hours'] ?? 0;
                            $totalActual += $item['actual_hours'] ?? 0;
                            $totalEarned += $item['earned_hours'] ?? 0;

                            $itemProductivity = ($item['actual_hours'] ?? 0) > 0
                                ? (($item['earned_hours'] ?? 0) / ($item['actual_hours'] ?? 0)) * 100
                                : 0;

                            $itemVariance = ($item['forecast'] ?? 0) - ($item['budget_hours'] ?? 0);

                            if ($itemProductivity >= 100) {
                                $productivityBg = 'bg-green-50';
                                $productivityText = 'text-green-600';
                            } elseif ($itemProductivity >= 90) {
                                $productivityBg = 'bg-yellow-50';
                                $productivityText = 'text-yellow-600';
                            } else {
                                $productivityBg = 'bg-red-50';
                                $productivityText = 'text-red-600';
                            }

                            $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                            $rowClass++;
                        @endphp
                        <tr class="{{ $bgClass }} border border-gray-300">
                            <td class="border border-gray-300 px-4 py-2 font-semibold">{{ $item['code'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['budget_hours'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['actual_hours'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['earned_hours'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right font-bold {{ $productivityText }}">
                                {{ number_format($itemProductivity, 1) }}%
                            </td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['forecast'] ?? 0, 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right {{ $itemVariance < 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                {{ number_format($itemVariance, 1) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-blue-100 border border-gray-300 font-bold">
                        <td class="border border-gray-300 px-4 py-2">TOTALS</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalBudget, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalActual, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalEarned, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">
                            @php
                                $totalProductivity = $totalActual > 0 ? ($totalEarned / $totalActual) * 100 : 0;
                                $totalProdColor = $totalProductivity >= 100 ? 'text-green-600' : ($totalProductivity >= 90 ? 'text-yellow-600' : 'text-red-600');
                            @endphp
                            <span class="{{ $totalProdColor }}">{{ number_format($totalProductivity, 1) }}%</span>
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($summary['forecast_at_completion'] ?? 0, 1) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">
                            @php
                                $totalVariance = ($summary['forecast_at_completion'] ?? 0) - $totalBudget;
                            @endphp
                            <span class="{{ $totalVariance < 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($totalVariance, 1) }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Productivity Key -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-8">
            <p class="font-semibold text-gray-700 mb-3">Productivity Status:</p>
            <div class="grid grid-cols-3 gap-4">
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 bg-green-500 rounded"></span>
                    <span class="text-sm text-gray-600">Good (>= 100%)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 bg-yellow-500 rounded"></span>
                    <span class="text-sm text-gray-600">Fair (90% - 99%)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 bg-red-500 rounded"></span>
                    <span class="text-sm text-gray-600">Poor (< 90%)</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-8 flex gap-4">
            <a href="{{ route('projects.reports.productivity', $project) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                Refresh
            </a>
            <a href="{{ route('projects.reports.productivity.pdf', $project) }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">
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
