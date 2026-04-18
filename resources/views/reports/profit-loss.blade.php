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
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Phase Code</th>
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

        <!-- P&L Bar Chart: Revenue vs Cost vs Profit per Phase Code -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-10 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Revenue vs Cost vs Profit — by Phase Code</h3>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-blue-500"></span>Revenue</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-500"></span>Cost</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-green-500"></span>Profit</span>
                </div>
            </div>
            @if(count($plData) > 0)
                <div class="relative" style="height: 360px;">
                    <canvas id="plChart"></canvas>
                </div>
            @else
                <div class="h-64 bg-gray-50 rounded border border-dashed border-gray-300 flex items-center justify-center">
                    <p class="text-gray-400 text-sm">No budget data available to chart.</p>
                </div>
            @endif
        </div>

        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('plChart');
            if (!canvas || typeof Chart === 'undefined') return;

            const plData = @json($plData);
            if (!plData || plData.length === 0) return;

            const labels   = plData.map(r => r.code + (r.name ? ' — ' + r.name : ''));
            const revenue  = plData.map(r => Number(r.revenue) || 0);
            const costs    = plData.map(r => Number(r.cost) || 0);
            const profit   = plData.map((r, i) => revenue[i] - costs[i]);

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Revenue', data: revenue, backgroundColor: 'rgba(59, 130, 246, 0.85)',  borderColor: 'rgba(59, 130, 246, 1)',  borderWidth: 1 },
                        { label: 'Cost',    data: costs,   backgroundColor: 'rgba(239, 68, 68, 0.85)',   borderColor: 'rgba(239, 68, 68, 1)',   borderWidth: 1 },
                        { label: 'Profit',  data: profit,  backgroundColor: 'rgba(34, 197, 94, 0.85)',   borderColor: 'rgba(34, 197, 94, 1)',   borderWidth: 1 },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const value = ctx.parsed.y;
                                    const formatted = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
                                    return ctx.dataset.label + ': ' + formatted;
                                }
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { maxRotation: 45, minRotation: 0, autoSkip: false, font: { size: 11 } } },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    if (Math.abs(value) >= 1000) return '$' + (value / 1000).toFixed(0) + 'k';
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
        @endpush

        <!-- Actions -->
        <div class="mt-8 flex gap-4">
            <a href="{{ route('projects.reports.profit-loss', $project) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                Refresh
            </a>
            <a href="{{ route('projects.reports.profit-loss.pdf', $project) }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">
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
