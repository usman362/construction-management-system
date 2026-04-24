@extends('layouts.app')
@section('title', 'RFIs')
@section('content')

@php
    $statusBadge = [
        'draft'     => 'bg-gray-100 text-gray-700 border-gray-200',
        'submitted' => 'bg-blue-100 text-blue-800 border-blue-200',
        'in_review' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'answered'  => 'bg-green-100 text-green-800 border-green-200',
        'closed'    => 'bg-slate-200 text-slate-800 border-slate-300',
    ];
    $priorityBadge = [
        'low'    => 'bg-gray-100 text-gray-700',
        'medium' => 'bg-blue-100 text-blue-700',
        'high'   => 'bg-amber-100 text-amber-800',
        'urgent' => 'bg-red-100 text-red-800',
    ];
@endphp

<div class="max-w-7xl mx-auto space-y-6 px-4 py-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">RFIs</h1>
            <p class="text-sm text-gray-500 mt-1">Portfolio-wide register of Requests for Information across all projects.</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Project</label>
                <select name="project_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All projects</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected((int)($filters['project_id'] ?? 0) === $p->id)>
                            {{ $p->project_number }} — {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Any</option>
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Priority</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Any</option>
                    @foreach($priorityLabels as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['priority'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Assigned To</label>
                <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Anyone</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((int)($filters['assigned_to'] ?? 0) === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <label class="inline-flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="overdue_only" value="1" class="h-4 w-4 mr-2" @checked(!empty($filters['overdue_only']))>
                    Overdue only
                </label>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Apply</button>
            <a href="{{ route('rfis.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg">Reset</a>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($rfis->isEmpty())
            <div class="py-12 text-center text-gray-400">
                <p class="text-sm">No RFIs found. Create RFIs from a project page.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">RFI #</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Project</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Subject</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Category</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Priority</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Assignee</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Needed By</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($rfis as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">
                                <a href="{{ route('projects.rfis.show', [$r->project_id, $r->id]) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                                    {{ $r->rfi_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('projects.show', $r->project) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $r->project->project_number ?? '—' }}
                                </a>
                                <div class="text-xs text-gray-500">{{ $r->project->name ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ \Illuminate\Support\Str::limit($r->subject, 60) }}</div>
                                <div class="text-xs text-gray-500">Submitted by {{ $r->submitter->name ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ $categoryLabels[$r->category] ?? $r->category }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded {{ $priorityBadge[$r->priority] ?? 'bg-gray-100' }}">
                                    {{ $priorityLabels[$r->priority] ?? $r->priority }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $r->assignee->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($r->needed_by)
                                    <span class="{{ $r->is_overdue ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                        {{ $r->needed_by->format('m/d/Y') }}
                                        @if($r->is_overdue)<span class="ml-1 text-[10px] uppercase">Overdue</span>@endif
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded border {{ $statusBadge[$r->status] ?? 'bg-gray-100' }}">
                                    {{ $statusLabels[$r->status] ?? $r->status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $rfis->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
