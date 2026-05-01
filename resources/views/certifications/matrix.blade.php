@extends('layouts.app')

@section('title', 'Certification Training Matrix')

{{--
    Certification Training Matrix — Brenda 2026-05-01.

    Single-page pivot view: every active employee × every cert type that
    appears on file. The cell shows the expiry date and a color-coded
    status badge so the office can scan for expirations at a glance.

    Print-friendly: drops sidebar / nav and switches to landscape via
    @media print rules so the whole grid fits on a wide sheet.
--}}

@push('styles')
<style>
    .matrix-wrap { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; }
    table.cert-matrix { border-collapse: collapse; width: 100%; font-size: 12px; }
    table.cert-matrix thead th {
        position: sticky; top: 0;
        background: #1f2937; color: #fff;
        padding: 8px 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; font-size: 10px;
        border-right: 1px solid #374151;
        white-space: nowrap;
    }
    table.cert-matrix thead th.emp-col {
        position: sticky; left: 0; z-index: 5;
        text-align: left; min-width: 200px;
    }
    table.cert-matrix tbody td {
        padding: 6px 8px;
        border: 1px solid #e5e7eb;
        text-align: center;
        white-space: nowrap;
    }
    table.cert-matrix tbody td.emp-cell {
        position: sticky; left: 0;
        background: #f9fafb;
        text-align: left;
        font-weight: 600;
        z-index: 1;
        min-width: 200px;
    }
    table.cert-matrix tbody tr:hover td { background: #fef9c3; }
    table.cert-matrix tbody tr:hover td.emp-cell { background: #fde68a; }

    /* Status pill — color coded */
    .pill {
        display: inline-block; padding: 2px 8px; border-radius: 999px;
        font-size: 10px; font-weight: 700; letter-spacing: 0.3px;
    }
    .pill-valid    { background: #d1fae5; color: #065f46; }
    .pill-soon     { background: #fef3c7; color: #92400e; }
    .pill-expired  { background: #fecaca; color: #991b1b; }
    .pill-missing  { background: transparent; color: #cbd5e1; font-weight: 400; }

    /* Subtle expiry date underneath the pill */
    .expiry-date { display: block; font-size: 9px; color: #6b7280; margin-top: 2px; }

    /* Legend */
    .legend { display: flex; gap: 14px; flex-wrap: wrap; font-size: 11px; color: #4b5563; align-items: center; }

    @media print {
        @page { size: Letter landscape; margin: 0.4in; }
        nav, .no-print, .legend-actions { display: none !important; }
        .matrix-wrap { overflow: visible; }
        body { background: #fff; }
        table.cert-matrix thead th { background: #111 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-4 flex-wrap gap-3 no-print">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Certification Training Matrix</h1>
            <p class="text-sm text-gray-500 mt-1">Active employees · {{ $certNames->count() }} certification type(s) tracked</p>
        </div>
        <div class="flex items-center gap-2 legend-actions">
            <a href="{{ route('employees.index') }}" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50">← Employees</a>
            {{-- 2026-05-01 (Brenda): Excel download for the safety department —
                 keeps current craft/status filters on the URL. --}}
            <a href="{{ route('certifications.matrix.excel', request()->only(['craft_id', 'status'])) }}"
               class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Download Excel
            </a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18M9.75 9V3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V9"/></svg>
                Print
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('certifications.matrix') }}" class="bg-white border border-gray-200 rounded-lg p-3 mb-4 flex flex-wrap items-end gap-3 no-print">
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Craft</label>
            <select name="craft_id" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
                <option value="">All crafts</option>
                @foreach ($crafts as $c)
                    <option value="{{ $c->id }}" {{ ($filters['craft_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Employee status</label>
            <select name="status" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
                <option value="active" {{ ($filters['status'] ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="" {{ ($filters['status'] ?? '') === '' ? 'selected' : '' }}>All</option>
            </select>
        </div>
        <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold px-4 py-1.5 rounded">Apply</button>
        <div class="legend ml-auto">
            <span><span class="pill pill-valid">Valid</span> &gt; 30 days</span>
            <span><span class="pill pill-soon">Soon</span> ≤ 30 days</span>
            <span><span class="pill pill-expired">Expired</span></span>
            <span><span class="pill pill-missing">—</span> Not on file</span>
        </div>
    </form>

    {{-- Matrix --}}
    @if ($employees->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
            No employees match the selected filter.
        </div>
    @elseif ($certNames->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center">
            <p class="text-gray-700 font-semibold mb-2">No certifications on file yet.</p>
            <p class="text-sm text-gray-500">Add certifications from any employee's detail page (Employees → click employee → Certifications tab) and they'll appear in this matrix.</p>
        </div>
    @else
        <div class="matrix-wrap">
            <table class="cert-matrix">
                <thead>
                    <tr>
                        <th class="emp-col">Employee</th>
                        <th>Craft</th>
                        @foreach ($certNames as $name)
                            <th>{{ $name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $emp)
                        <tr>
                            <td class="emp-cell">
                                <a href="{{ route('employees.show', $emp) }}" class="text-blue-600 hover:underline">
                                    {{ $emp->last_name }}, {{ $emp->first_name }}
                                </a>
                                <div class="text-[10px] text-gray-500">#{{ $emp->employee_number }}</div>
                            </td>
                            <td class="text-xs text-gray-700">{{ $emp->craft->name ?? '—' }}</td>
                            @foreach ($certNames as $name)
                                @php
                                    $cert = $matrix[$emp->id . '|' . $name] ?? null;
                                    $status = $cert?->status ?? 'missing';
                                @endphp
                                <td>
                                    @if (! $cert)
                                        <span class="pill pill-missing">—</span>
                                    @else
                                        @php
                                            $pill = match ($status) {
                                                'expired'       => 'pill-expired',
                                                'expiring_soon' => 'pill-soon',
                                                default         => 'pill-valid',
                                            };
                                            $label = match ($status) {
                                                'expired'       => 'Expired',
                                                'expiring_soon' => 'Soon',
                                                default         => 'Valid',
                                            };
                                        @endphp
                                        <span class="pill {{ $pill }}">{{ $label }}</span>
                                        @if ($cert->expiry_date)
                                            <span class="expiry-date">exp {{ $cert->expiry_date->format('M j, Y') }}</span>
                                        @elseif ($cert->issue_date)
                                            <span class="expiry-date">iss {{ $cert->issue_date->format('M j, Y') }}</span>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Summary counts under the matrix --}}
        @php
            $counts = ['valid' => 0, 'expiring_soon' => 0, 'expired' => 0];
            foreach ($matrix as $cert) {
                $counts[$cert->status]++;
            }
            $totalCells = $employees->count() * $certNames->count();
            $missing = $totalCells - count($matrix);
        @endphp
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3 no-print">
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <p class="text-xs text-green-700 font-semibold uppercase">Valid</p>
                <p class="text-2xl font-bold text-green-800">{{ $counts['valid'] }}</p>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                <p class="text-xs text-amber-700 font-semibold uppercase">Expiring within 30 days</p>
                <p class="text-2xl font-bold text-amber-800">{{ $counts['expiring_soon'] }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-xs text-red-700 font-semibold uppercase">Expired</p>
                <p class="text-2xl font-bold text-red-800">{{ $counts['expired'] }}</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <p class="text-xs text-gray-700 font-semibold uppercase">Not on file</p>
                <p class="text-2xl font-bold text-gray-700">{{ $missing }}</p>
            </div>
        </div>
    @endif

</div>
@endsection
