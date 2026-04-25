@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-lg p-8 mb-8 text-white">
        <h2 class="text-3xl font-bold mb-2">Welcome to BuildTrack</h2>
        <p class="text-blue-100">{{ now()->format('l, F j, Y') }} - Manage your construction projects with ease</p>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         KPI TILES — click-through where a destination makes sense.
         Tile set redesigned in Phase 1 to surface risk signals at a glance:
         over-budget projects, near-budget projects, expiring certs.
         ═══════════════════════════════════════════════════════════════════ --}}
    {{-- ═══════════════════════════════════════════════════════════════════
         PHASE 7C — LIVE OPS STRIP
         Right-now reality at a glance. Independent of the longer-term KPI
         tiles below — this is "what's happening today" not "what's the trend."
         ═══════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {{-- Clocked in right now --}}
        <a href="{{ route('time-clock.admin') }}" class="bg-white rounded-lg shadow hover:shadow-md transition p-4 border-l-4 {{ ($stats['clockedInNow'] ?? 0) > 0 ? 'border-emerald-500' : 'border-gray-300' }} block">
            <div class="flex items-center justify-between">
                <p class="text-gray-600 text-xs font-medium uppercase tracking-wide">Clocked In Now</p>
                @if(($stats['clockedInNow'] ?? 0) > 0)
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                @endif
            </div>
            <p class="text-3xl font-bold {{ ($stats['clockedInNow'] ?? 0) > 0 ? 'text-emerald-600' : 'text-gray-900' }} mt-2">{{ $stats['clockedInNow'] ?? 0 }}</p>
            <p class="text-[11px] text-gray-500 mt-1">On the job right now</p>
        </a>

        {{-- Pending approvals (combined) --}}
        <div class="bg-white rounded-lg shadow p-4 border-l-4 {{ ($stats['pendingApprovalsTotal'] ?? 0) > 0 ? 'border-amber-500' : 'border-gray-300' }}">
            <p class="text-gray-600 text-xs font-medium uppercase tracking-wide">Pending Approvals</p>
            <p class="text-3xl font-bold {{ ($stats['pendingApprovalsTotal'] ?? 0) > 0 ? 'text-amber-600' : 'text-gray-900' }} mt-2">{{ $stats['pendingApprovalsTotal'] ?? 0 }}</p>
            <p class="text-[11px] text-gray-500 mt-1">
                {{ $stats['pendingTimesheets'] ?? 0 }} TS · {{ $stats['openChangeOrders'] ?? 0 }} CO · {{ $stats['pendingInvoices'] ?? 0 }} Inv
            </p>
        </div>

        {{-- Billed this month --}}
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-gray-600 text-xs font-medium uppercase tracking-wide">Billed This Month</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">${{ number_format($stats['billedThisMonth'] ?? 0, 0) }}</p>
            <p class="text-[11px] text-gray-500 mt-1">{{ now()->format('F Y') }} invoices issued</p>
        </div>

        {{-- Collected this month --}}
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-gray-600 text-xs font-medium uppercase tracking-wide">Collected This Month</p>
            <p class="text-2xl font-bold text-green-700 mt-2">${{ number_format($stats['collectedThisMonth'] ?? 0, 0) }}</p>
            @php
                $billed = (float) ($stats['billedThisMonth'] ?? 0);
                $collected = (float) ($stats['collectedThisMonth'] ?? 0);
                $pct = $billed > 0 ? min(100, round(($collected / $billed) * 100)) : 0;
            @endphp
            <p class="text-[11px] text-gray-500 mt-1">{{ $pct }}% of billed</p>
        </div>
    </div>

    {{-- ── Live: Currently clocked-in workers (only render when count > 0) ── --}}
    @if(($stats['clockedInNow'] ?? 0) > 0)
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-emerald-900 flex items-center gap-2">
                    <span class="flex h-2 w-2"><span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span></span>
                    On the clock right now
                </h3>
                <a href="{{ route('time-clock.admin') }}" class="text-xs text-emerald-700 hover:underline">Review all &rarr;</a>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($clockedInList ?? [] as $entry)
                    <div class="bg-white border border-emerald-200 rounded-md px-3 py-1.5 text-xs">
                        <span class="font-semibold text-gray-900">
                            {{ $entry->employee?->first_name ?? '—' }} {{ $entry->employee?->last_name ?? '' }}
                        </span>
                        <span class="text-gray-500"> · {{ $entry->project?->project_number ?? '—' }}</span>
                        <span class="text-emerald-600 ml-1" title="Since {{ $entry->clock_in_at->format('g:i A') }}">
                            {{ $entry->clock_in_at->diffForHumans(null, true) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <!-- Active Projects -->
        <a href="{{ route('projects.index') }}" class="bg-white rounded-lg shadow hover:shadow-md transition p-4 border-l-4 border-blue-600 block">
            <p class="text-gray-600 text-xs font-medium uppercase tracking-wide">Active Projects</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['activeProjects'] ?? 0 }}</p>
            <p class="text-[11px] text-blue-600 mt-1">View all &rarr;</p>
        </a>

        <!-- Over Budget (risk: committed exceeds budget) -->
        <a href="#certifications-watch" onclick="document.getElementById('projects-section').scrollIntoView({behavior:'smooth'}); return false;" class="bg-white rounded-lg shadow hover:shadow-md transition p-4 border-l-4 {{ ($stats['overBudget'] ?? 0) > 0 ? 'border-red-600' : 'border-gray-300' }} block">
            <p class="text-gray-600 text-xs font-medium uppercase tracking-wide">Over Budget</p>
            <p class="text-3xl font-bold {{ ($stats['overBudget'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-900' }} mt-2">{{ $stats['overBudget'] ?? 0 }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Committed &gt; budget</p>
        </a>

        <!-- Near Budget (90-100% committed — early warning) -->
        <a href="#projects-section" onclick="document.getElementById('projects-section').scrollIntoView({behavior:'smooth'}); return false;" class="bg-white rounded-lg shadow hover:shadow-md transition p-4 border-l-4 {{ ($stats['nearBudget'] ?? 0) > 0 ? 'border-amber-500' : 'border-gray-300' }} block">
            <p class="text-gray-600 text-xs font-medium uppercase tracking-wide">&ge;90% Committed</p>
            <p class="text-3xl font-bold {{ ($stats['nearBudget'] ?? 0) > 0 ? 'text-amber-600' : 'text-gray-900' }} mt-2">{{ $stats['nearBudget'] ?? 0 }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Approaching budget</p>
        </a>

        <!-- Pending Timesheet Approvals -->
        <a href="{{ route('timesheets.index') }}?status=pending" class="bg-white rounded-lg shadow hover:shadow-md transition p-4 border-l-4 border-yellow-500 block">
            <p class="text-gray-600 text-xs font-medium uppercase tracking-wide">Pending T-Sheets</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['pendingTimesheets'] ?? 0 }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Awaiting approval</p>
        </a>

        <!-- Open Change Orders -->
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-600 block">
            <p class="text-gray-600 text-xs font-medium uppercase tracking-wide">Open COs</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['openChangeOrders'] ?? 0 }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Pending approval</p>
        </div>

        <!-- Expiring Certifications (combined: expired + next 30 days) -->
        @php $certAlerts = ($stats['expiredCerts'] ?? 0) + ($stats['expiring30Certs'] ?? 0); @endphp
        <a href="#certifications-watch" onclick="document.getElementById('certifications-watch').scrollIntoView({behavior:'smooth'}); return false;" class="bg-white rounded-lg shadow hover:shadow-md transition p-4 border-l-4 {{ $certAlerts > 0 ? 'border-rose-600' : 'border-green-600' }} block">
            <p class="text-gray-600 text-xs font-medium uppercase tracking-wide">Cert Alerts</p>
            <p class="text-3xl font-bold {{ $certAlerts > 0 ? 'text-rose-600' : 'text-gray-900' }} mt-2">{{ $certAlerts }}</p>
            <p class="text-[11px] text-gray-500 mt-1">
                {{ $stats['expiredCerts'] ?? 0 }} expired &bull; {{ $stats['expiring30Certs'] ?? 0 }} in 30d
            </p>
        </a>
    </div>

    <!-- Recent Projects Table -->
    <div id="projects-section" class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">Projects</h3>
            <span class="text-xs text-gray-500">{{ count($recentProjects ?? []) }} active</span>
        </div>
        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
            <table id="dashboardProjectsTable" class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                    {{-- Column headers are click-to-sort. `data-sort-type` tells the JS
                         which comparator to use ('string' / 'number' / 'date'). The first
                         click sorts ascending; a second click on the same column toggles
                         to descending. An arrow indicator shows the current direction. --}}
                    <tr>
                        <th data-sort-type="string" class="dash-sort px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100">
                            Project # <span class="sort-ind text-gray-400">↕</span>
                        </th>
                        <th data-sort-type="string" class="dash-sort px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100">
                            Project <span class="sort-ind text-gray-400">↕</span>
                        </th>
                        <th data-sort-type="string" class="dash-sort px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100">
                            Client <span class="sort-ind text-gray-400">↕</span>
                        </th>
                        <th data-sort-type="string" class="dash-sort px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100">
                            Status <span class="sort-ind text-gray-400">↕</span>
                        </th>
                        <th data-sort-type="number" class="dash-sort px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100">
                            Estimate <span class="sort-ind text-gray-400">↕</span>
                        </th>
                        <th data-sort-type="number" class="dash-sort px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100">
                            Budget <span class="sort-ind text-gray-400">↕</span>
                        </th>
                        <th data-sort-type="number" class="dash-sort px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100">
                            % Committed <span class="sort-ind text-gray-400">↕</span>
                        </th>
                        <th data-sort-type="number" class="dash-sort px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100">
                            Profit Margin <span class="sort-ind text-gray-400">↕</span>
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentProjects ?? [] as $project)
                        <tr class="hover:bg-gray-50 transition">
                            <td data-sort-value="{{ $project->project_number ?? '' }}" class="px-4 py-4 whitespace-nowrap font-mono text-sm font-semibold text-blue-700">{{ $project->project_number ?? '—' }}</td>
                            <td data-sort-value="{{ $project->name ?? '' }}" class="px-4 py-4 whitespace-nowrap">
                                <p class="font-medium text-gray-900">{{ $project->name ?? 'N/A' }}</p>
                            </td>
                            <td data-sort-value="{{ $project->client?->name ?? '' }}" class="px-4 py-4 whitespace-nowrap text-gray-600">{{ $project->client?->name ?? 'N/A' }}</td>
                            <td data-sort-value="{{ $project->status ?? '' }}" class="px-4 py-4 whitespace-nowrap">
                                @if(($project->status ?? null) === 'active')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                @elseif(($project->status ?? null) === 'completed')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Completed</span>
                                @elseif(($project->status ?? null) === 'awarded')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">Awarded</span>
                                @elseif(($project->status ?? null) === 'bidding')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">Bidding</span>
                                @elseif(($project->status ?? null) === 'on_hold')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">On Hold</span>
                                @else
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($project->status ?? 'Unknown') }}</span>
                                @endif
                            </td>
                            <td data-sort-value="{{ (float) ($project->dashboard_estimate ?? $project->estimate ?? 0) }}" class="px-4 py-4 whitespace-nowrap font-medium text-right">${{ number_format($project->dashboard_estimate ?? $project->estimate ?? 0, 2) }}</td>
                            <td data-sort-value="{{ (float) ($project->dashboard_budget ?? $project->budget ?? 0) }}" class="px-4 py-4 whitespace-nowrap font-medium text-right">${{ number_format($project->dashboard_budget ?? $project->budget ?? 0, 2) }}</td>
                            <td data-sort-value="{{ (float) ($project->committed_percentage ?? 0) }}" class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($project->committed_percentage ?? 0) }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ ($project->committed_percentage ?? 0) }}%</span>
                                </div>
                            </td>
                            <td data-sort-value="{{ (float) ($project->profit_margin ?? 0) }}" class="px-4 py-4 whitespace-nowrap">
                                <span class="font-medium {{ ($project->profit_margin ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ ($project->profit_margin ?? 0) >= 0 ? '+' : '' }}{{ number_format($project->profit_margin ?? 0, 1) }}%
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition" title="View">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">No projects found. Create your first project to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         CERTIFICATIONS WATCH — Phase 1 widget
         Pulls every cert with an expiry_date, buckets by urgency, and shows
         the 20 most urgent so the PM/admin can renew before they lapse.
         ═══════════════════════════════════════════════════════════════════ --}}
    <div id="certifications-watch" class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold text-gray-900">Certifications Watch</h3>
                <span class="text-xs text-gray-500">Next 90 days + already expired</span>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="px-2 py-1 rounded-full bg-rose-100 text-rose-800 font-semibold">
                    {{ $stats['expiredCerts'] ?? 0 }} Expired
                </span>
                <span class="px-2 py-1 rounded-full bg-red-100 text-red-800 font-semibold">
                    {{ $stats['expiring30Certs'] ?? 0 }} in 30 days
                </span>
                <span class="px-2 py-1 rounded-full bg-amber-100 text-amber-800 font-semibold">
                    {{ $stats['expiring60Certs'] ?? 0 }} in 31–60 days
                </span>
                <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 font-semibold">
                    {{ $stats['expiring90Certs'] ?? 0 }} in 61–90 days
                </span>
            </div>
        </div>
        <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Certification</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Issuing Authority</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Expiry Date</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($certWatchList ?? [] as $cert)
                        @php
                            $days = (int) floor(\Carbon\Carbon::now()->startOfDay()->diffInDays($cert->expiry_date, false));
                            // Bucket for badge styling:
                            if ($days < 0)        { $badge = 'bg-rose-100 text-rose-800';    $label = 'Expired ' . abs($days) . 'd ago'; }
                            elseif ($days <= 30)  { $badge = 'bg-red-100 text-red-800';      $label = 'Expires in ' . $days . 'd'; }
                            elseif ($days <= 60)  { $badge = 'bg-amber-100 text-amber-800';  $label = 'Expires in ' . $days . 'd'; }
                            else                  { $badge = 'bg-yellow-100 text-yellow-800';$label = 'Expires in ' . $days . 'd'; }
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="font-medium text-gray-900">{{ $cert->employee?->first_name }} {{ $cert->employee?->last_name }}</p>
                                @if($cert->employee?->employee_number)
                                    <p class="text-[11px] text-gray-500 font-mono">#{{ $cert->employee->employee_number }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="text-sm text-gray-900">{{ $cert->name }}</p>
                                @if($cert->certification_number)
                                    <p class="text-[11px] text-gray-500">#{{ $cert->certification_number }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ $cert->issuing_authority ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-900 font-semibold">
                                {{ $cert->expiry_date->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badge }}">{{ $label }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @if($cert->employee_id)
                                    <a href="{{ route('employees.show', $cert->employee_id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50" title="Open employee">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </a>
                                @endif
                                @if($cert->file_path)
                                    <a href="{{ route('certifications.download', $cert->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-600 hover:bg-gray-100" title="Download file">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <svg class="w-10 h-10 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                All certifications are current. Nothing expires in the next 90 days.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Action Buttons -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('projects.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow transition duration-200 text-center flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>New Project</span>
        </a>
        <a href="{{ route('timesheets.bulk-create') }}" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg shadow transition duration-200 text-center flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>New Timesheet</span>
        </a>
        <button onclick="document.getElementById('coProjectModal').classList.remove('hidden')" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg shadow transition duration-200 text-center flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>New Change Order</span>
        </button>
    </div>

    <!-- Project Picker Modal for Change Orders -->
    <div id="coProjectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.5)" onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Select Project for Change Order</h3>
            <select id="coProjectSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none bg-white mb-4">
                <option value="">Choose a project...</option>
                @foreach($allProjects ?? [] as $proj)
                    <option value="{{ $proj->id }}">{{ $proj->name }} ({{ $proj->project_number }})</option>
                @endforeach
            </select>
            <div class="flex gap-3 justify-end">
                <button onclick="document.getElementById('coProjectModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button onclick="goToChangeOrders()" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700">Go to Change Orders</button>
            </div>
        </div>
    </div>

    <script>
    function goToChangeOrders() {
        var projectId = document.getElementById('coProjectSelect').value;
        if (!projectId) { alert('Please select a project first.'); return; }
        window.location.href = window.BASE_URL+'/projects/' + projectId + '/change-orders';
    }

    // ─── Dashboard Projects table: client-side sort ────────────────────
    // Click a header → sort rows by that column. Click same header again
    // to reverse direction. Numeric columns expose their raw value via
    // `data-sort-value` on the <td> so "$538,865.74" compares as a number.
    (function () {
        const table = document.getElementById('dashboardProjectsTable');
        if (!table) return;
        const tbody = table.querySelector('tbody');
        const headers = table.querySelectorAll('th.dash-sort');

        headers.forEach((th, idx) => {
            th.addEventListener('click', () => {
                const type = th.dataset.sortType || 'string';
                const current = th.dataset.sortDir === 'asc' ? 'desc' : 'asc';

                // Reset all arrow indicators, then mark the active one.
                headers.forEach(h => {
                    h.dataset.sortDir = '';
                    const ind = h.querySelector('.sort-ind');
                    if (ind) { ind.textContent = '↕'; ind.className = 'sort-ind text-gray-400'; }
                });
                th.dataset.sortDir = current;
                const ind = th.querySelector('.sort-ind');
                if (ind) { ind.textContent = current === 'asc' ? '↑' : '↓'; ind.className = 'sort-ind text-blue-600'; }

                // Skip the empty-state row (colspan=9) if it's the only one.
                const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.children.length > 1);
                rows.sort((a, b) => {
                    const av = (a.children[idx]?.dataset.sortValue ?? a.children[idx]?.textContent ?? '').trim();
                    const bv = (b.children[idx]?.dataset.sortValue ?? b.children[idx]?.textContent ?? '').trim();
                    let cmp;
                    if (type === 'number') {
                        cmp = (parseFloat(av) || 0) - (parseFloat(bv) || 0);
                    } else {
                        cmp = av.localeCompare(bv, undefined, { numeric: true, sensitivity: 'base' });
                    }
                    return current === 'asc' ? cmp : -cmp;
                });
                rows.forEach(r => tbody.appendChild(r));
            });
        });
    })();
    </script>
@endsection
