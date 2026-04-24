@extends('layouts.app')
@section('title', 'Lien Waivers')
@section('content')

@php
    $statusBadge = [
        'pending'  => 'bg-amber-100 text-amber-800 border-amber-200',
        'received' => 'bg-green-100 text-green-800 border-green-200',
        'rejected' => 'bg-red-100 text-red-800 border-red-200',
    ];
@endphp

<div class="max-w-7xl mx-auto space-y-6 px-4 py-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Lien Waivers</h1>
            <p class="text-sm text-gray-500 mt-1">Portfolio-wide register of conditional and unconditional lien waivers from subs & suppliers.</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    @foreach(['pending', 'received', 'rejected'] as $s)
                        <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Type</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Any</option>
                    @foreach($typeLabels as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Apply</button>
                <a href="{{ route('lien-waivers.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg">Reset</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($waivers->isEmpty())
            <div class="py-12 text-center text-gray-400">
                <p class="text-sm">No lien waivers recorded yet. Add waivers from each project page.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Project</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Vendor / Sub</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">PO / Commitment</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Type</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Amount</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Through</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Received</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($waivers as $w)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('projects.show', $w->project) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $w->project->project_number ?? '—' }}
                                </a>
                                <div class="text-xs text-gray-500">{{ $w->project->name ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $w->vendor->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-600">
                                {{ $w->commitment?->commitment_number ?? $w->commitment?->po_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs">{{ $typeLabels[$w->type] ?? $w->type }}</td>
                            <td class="px-4 py-3 text-right font-medium">${{ number_format((float)$w->amount, 2) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $w->through_date?->format('m/d/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $w->received_date?->format('m/d/Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded border {{ $statusBadge[$w->status] ?? 'bg-gray-100' }}">
                                    {{ ucfirst($w->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $waivers->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
