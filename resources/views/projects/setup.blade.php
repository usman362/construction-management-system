@extends('layouts.app')

@section('title', 'Setup — ' . $project->name)

@section('content')
{{--
    2026-05-23 (Brenda): "There needs to be a set up tab / template
    this needs to contain all pertenant information. Labor Rates,
    Equipment Rates, Project Markups etc."

    Mirror of KH's Excel "Estimate Summary" header strip — one place
    that surfaces everything the user needs to verify before they
    start building an estimate / CO / commitment on this project.
    Each section has a "Manage" link to the existing editor page.
--}}
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:underline">&larr; Back to {{ $project->project_number }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Project Setup</h1>
            <p class="text-sm text-gray-500">{{ $project->name }} · {{ $project->client?->name ?? 'No client' }}</p>
        </div>
    </div>

    {{-- ───── 1. Project Details ───── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-bold text-gray-900">Project Details</h2>
            <a href="{{ route('projects.show', $project) }}" class="text-xs text-blue-600 hover:underline">Edit on project page</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">Project #</div>
                <div class="font-mono">{{ $project->project_number }}</div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">Client</div>
                <div>{{ $project->client?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">Status</div>
                <div class="capitalize">{{ str_replace('_', ' ', $project->status ?? '—') }}</div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">Duration</div>
                <div>
                    @if($project->start_date && $project->end_date)
                        {{ \Carbon\Carbon::parse($project->start_date)->diffInDays(\Carbon\Carbon::parse($project->end_date)) + 1 }} days
                        <div class="text-[10px] text-gray-500">{{ \Carbon\Carbon::parse($project->start_date)->format('M j') }} – {{ \Carbon\Carbon::parse($project->end_date)->format('M j, Y') }}</div>
                    @else
                        <span class="text-gray-400">— set start/end dates —</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">PO Number</div>
                <div>{{ $project->po_number ?: '—' }}</div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">PO Date</div>
                <div>{{ $project->po_date ? \Carbon\Carbon::parse($project->po_date)->format('M j, Y') : '—' }}</div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">Contract Value</div>
                <div>${{ number_format((float) ($project->contract_value ?? 0), 2) }}</div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">Per Diem Rate</div>
                <div>${{ number_format((float) ($project->default_per_diem_rate ?? 0), 2) }}/day</div>
            </div>
        </div>
    </div>

    {{-- ───── 2. Labor Rates ───── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <div>
                <h2 class="text-base font-bold text-gray-900">Labor Rates (per Craft)</h2>
                <p class="text-xs text-gray-500">Project-specific Base / Billable rates. These override the craft master when used in estimates + the Labor tile.</p>
            </div>
            <a href="{{ route('projects.billable-rates.index', $project) }}" class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1.5 rounded">Manage Billable Rates →</a>
        </div>
        @if($rates->isEmpty())
            <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded p-3">
                ⚠ No project-specific rates set yet. Estimates will use the craft master rates. <a href="{{ route('projects.billable-rates.index', $project) }}" class="font-semibold underline">Add rates →</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500 border-b">
                        <tr>
                            <th class="text-left py-2">Craft</th>
                            <th class="text-left">Employee Override</th>
                            <th class="text-right">Base ST</th>
                            <th class="text-right">Base OT</th>
                            <th class="text-right">ST Billable</th>
                            <th class="text-right">OT Billable</th>
                            <th class="text-left">Effective</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rates as $r)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2">{{ $r->craft?->code ?? '—' }} <span class="text-gray-500 text-xs">{{ $r->craft?->name }}</span></td>
                                <td>{{ $r->employee ? trim($r->employee->first_name . ' ' . $r->employee->last_name) : '—' }}</td>
                                <td class="text-right">${{ number_format((float) $r->base_hourly_rate, 2) }}</td>
                                <td class="text-right">{{ $r->base_ot_hourly_rate ? '$' . number_format((float) $r->base_ot_hourly_rate, 2) : '—' }}</td>
                                <td class="text-right font-semibold text-blue-700">${{ number_format((float) $r->straight_time_rate, 2) }}</td>
                                <td class="text-right font-semibold text-blue-700">${{ number_format((float) $r->overtime_rate, 2) }}</td>
                                <td class="text-xs">{{ $r->effective_date ? \Carbon\Carbon::parse($r->effective_date)->format('M j, Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($missingCrafts->isNotEmpty())
                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded text-xs">
                    <strong class="text-amber-900">⚠ Crafts without project-specific rates ({{ $missingCrafts->count() }}):</strong>
                    <span class="text-amber-800"> {{ $missingCrafts->pluck('code')->take(15)->implode(', ') }}{{ $missingCrafts->count() > 15 ? '…' : '' }}</span>
                    <div class="text-amber-700 mt-1">These crafts will use the craft master rates if used on this project's estimates.</div>
                </div>
            @endif
        @endif
    </div>

    {{-- ───── 3. Equipment Rates ───── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <div>
                <h2 class="text-base font-bold text-gray-900">Equipment Rates</h2>
                <p class="text-xs text-gray-500">Catalog of equipment available for estimates and assignments. Rates are global, set once per piece.</p>
            </div>
            <a href="{{ route('equipment.index') }}" class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1.5 rounded">Manage Equipment →</a>
        </div>
        @if($equipment->isEmpty())
            <p class="text-sm text-gray-500">No equipment in the catalog yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500 border-b">
                        <tr>
                            <th class="text-left py-2">Equipment</th>
                            <th class="text-left">Type</th>
                            <th class="text-right">Daily</th>
                            <th class="text-right">Weekly</th>
                            <th class="text-right">Monthly</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($equipment as $e)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2">{{ $e->name }}</td>
                                <td class="text-gray-600 text-xs">{{ $e->type ?? '—' }}</td>
                                <td class="text-right">{{ $e->daily_rate   ? '$' . number_format((float) $e->daily_rate, 2)   : '—' }}</td>
                                <td class="text-right">{{ $e->weekly_rate  ? '$' . number_format((float) $e->weekly_rate, 2)  : '—' }}</td>
                                <td class="text-right">{{ $e->monthly_rate ? '$' . number_format((float) $e->monthly_rate, 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ───── 4. Quick links ───── --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
        <h2 class="text-base font-bold text-blue-900 mb-3">Once setup is complete</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('projects.estimates.index', $project) }}" class="text-sm bg-white border border-blue-300 hover:bg-blue-100 text-blue-800 font-semibold px-3 py-2 rounded">Start an Estimate →</a>
            <a href="{{ route('projects.change-orders.index', $project) }}" class="text-sm bg-white border border-blue-300 hover:bg-blue-100 text-blue-800 font-semibold px-3 py-2 rounded">Create a Change Order →</a>
            <a href="{{ route('projects.budget.index', $project) }}" class="text-sm bg-white border border-blue-300 hover:bg-blue-100 text-blue-800 font-semibold px-3 py-2 rounded">Manage Budget Lines →</a>
            <a href="{{ route('projects.commitments.index', $project) }}" class="text-sm bg-white border border-blue-300 hover:bg-blue-100 text-blue-800 font-semibold px-3 py-2 rounded">Open Commitments →</a>
        </div>
    </div>
</div>
@endsection
