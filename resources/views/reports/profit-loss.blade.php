@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Profit & Loss - {{ $project->name ?? 'N/A' }}</h1>

        <!-- Summary Bar -->
        <div class="grid grid-cols-4 gap-4 mb-10">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Total Revenue</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">${{ number_format($summary['total_revenue'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-gradient-to-br from-red-50 to-red-100 p-6 rounded-lg border border-red-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Total Cost</p>
                <p class="text-3xl font-bold text-red-600 mt-2">${{ number_format($summary['total_cost'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border border-green-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Gross Profit</p>
                @php
                    $profit = ($summary['total_revenue'] ?? 0) - ($summary['total_cost'] ?? 0);
                @endphp
                <p class="text-3xl font-bold {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">${{ number_format($profit, 2) }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg border border-purple-200">
                <p class="text-xs font-semibold text-gray-600 uppercase">Margin %</p>
                @php
                    $marginPercent = ($summary['total_revenue'] ?? 0) > 0 ? ($profit / ($summary['total_revenue'] ?? 0)) * 100 : 0;
                @endphp
                <p class="text-3xl font-bold {{ $marginPercent >= 0 ? 'text-purple-600' : 'text-red-600' }} mt-2">{{ number_format($marginPercent, 1) }}%</p>
            </div>
        </div>

        <!-- P&L Table -->
        <div class="overflow-x-auto mb-10">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-blue-100 border border-gray-300">
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Cost Code</th>
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Name</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Revenue</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Cost</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Profit</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalRevenue = 0;
                        $totalCost = 0;
                        $rowClass = 0;
                    @endphp
                    @foreach($plData as $item)
                        @php
                            $totalRevenue += $item['revenue'] ?? 0;
                            $totalCost += $item['cost'] ?? 0;
                            $profit = ($item['revenue'] ?? 0) - ($item['cost'] ?? 0);
                            $margin = ($item['revenue'] ?? 0) > 0 ? ($profit / ($item['revenue'] ?? 0)) * 100 : 0;
                            $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                            $rowClass++;
                        @endphp
                        <tr class="{{ $bgClass }} border border-gray-300">
                            <td class="border border-gray-300 px-4 py-2 font-semibold">{{ $item['code'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $item['name'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($item['revenue'] ?? 0, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($item['cost'] ?? 0, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right font-semibold {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($profit, 2) }}
                            </td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($margin, 1) }}%</td>
                        </tr>
                    @endforeach
                    <tr class="bg-blue-100 border border-gray-300 font-bold">
                        <td colspan="2" class="border border-gray-300 px-4 py-2">TOTALS</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalRevenue, 2) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalCost, 2) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right font-bold {{ ($totalRevenue - $totalCost) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format($totalRevenue - $totalCost, 2) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-right">
                            @php
                                $totalMargin = $totalRevenue > 0 ? (($totalRevenue - $totalCost) / $totalRevenue) * 100 : 0;
                            @endphp
                            {{ number_format($totalMargin, 1) }}%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Chart Placeholder -->
        <div class="bg-gray-100 border-2 border-gray-300 rounded-lg p-12 text-center mb-10">
            <p class="text-gray-500 font-semibold mb-4">Profit & Loss Chart</p>
            <div class="h-64 bg-white rounded border border-gray-300 flex items-center justify-center">
                <p class="text-gray-400">Chart visualization area</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-8 flex gap-4">
            <a href="{{ route('reports.profit-loss', ['project' => $project->id]) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                Refresh
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
