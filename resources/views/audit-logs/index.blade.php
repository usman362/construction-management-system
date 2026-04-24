@extends('layouts.app')
@section('title', 'Audit Log')
@section('content')

@php
    $eventBadge = [
        'created'  => 'bg-green-100 text-green-800 border-green-200',
        'updated'  => 'bg-blue-100 text-blue-800 border-blue-200',
        'deleted'  => 'bg-red-100 text-red-800 border-red-200',
        'restored' => 'bg-amber-100 text-amber-800 border-amber-200',
    ];

    $entityLabel = function ($fqcn) {
        $base = class_basename($fqcn ?? '');
        return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $base));
    };
@endphp

<div class="max-w-7xl mx-auto space-y-6 px-4 py-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Audit Log</h1>
            <p class="text-sm text-gray-500 mt-1">Append-only history of changes to Timesheets, Change Orders, and Invoices.</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Entity</label>
                <select name="entity_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All entities</option>
                    @foreach($entityTypes as $type)
                        <option value="{{ $type }}" @selected(($filters['entity_type'] ?? '') === $type)>{{ $entityLabel($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Event</label>
                <select name="event" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Any</option>
                    @foreach(['created', 'updated', 'deleted', 'restored'] as $ev)
                        <option value="{{ $ev }}" @selected(($filters['event'] ?? '') === $ev)>{{ ucfirst($ev) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">User</label>
                <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Anyone</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((int)($filters['user_id'] ?? 0) === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Apply</button>
            <a href="{{ route('admin.audit-logs.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg">Reset</a>
        </div>
    </form>

    <!-- Log table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($logs->isEmpty())
            <div class="py-12 text-center text-gray-400">
                <p class="text-sm">No audit records match these filters yet.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">When</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">User</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Event</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Entity</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Changes</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($logs as $log)
                        <tr class="align-top hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                <div>{{ $log->created_at->format('m/d/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $log->created_at->format('g:i:s A') }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $log->user_name ?? '—' }}
                                @if(!$log->user_id && $log->user_name)
                                    <span class="text-xs text-gray-400">(deleted)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded border {{ $eventBadge[$log->event] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                    {{ ucfirst($log->event) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-medium text-gray-800">{{ $entityLabel($log->auditable_type) }}</div>
                                <div class="text-xs text-gray-500">#{{ $log->auditable_id }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($log->event === 'updated' && is_array($log->changes))
                                    <div class="space-y-1">
                                        @foreach($log->changes as $field => $diff)
                                            <div class="text-xs">
                                                <span class="font-semibold text-gray-700">{{ $field }}:</span>
                                                <span class="line-through text-red-600">{{ \Illuminate\Support\Str::limit((string)($diff['old'] ?? 'null'), 40) }}</span>
                                                <span class="text-gray-400">→</span>
                                                <span class="text-green-700 font-medium">{{ \Illuminate\Support\Str::limit((string)($diff['new'] ?? 'null'), 40) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif(is_array($log->changes) && count($log->changes) > 0)
                                    <details class="cursor-pointer">
                                        <summary class="text-xs text-blue-600 hover:text-blue-800">{{ count($log->changes) }} field(s) — view</summary>
                                        <div class="mt-2 bg-gray-50 rounded p-2 text-xs font-mono max-w-md overflow-auto">
                                            @foreach($log->changes as $k => $v)
                                                <div><span class="text-gray-500">{{ $k }}:</span> {{ \Illuminate\Support\Str::limit(is_scalar($v) ? (string)$v : json_encode($v), 60) }}</div>
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 font-mono">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
