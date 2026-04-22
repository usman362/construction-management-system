@extends('layouts.app')

@section('title', 'Timesheet Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    @if (session('success'))
        <div class="mb-4 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('timesheets.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Timesheets</a>
        <div class="space-x-2">
            <a href="{{ route('timesheets.print', $timesheet) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
                Print
            </a>
            <a href="{{ route('timesheets.print', [$timesheet, 'mode' => 'pdf']) }}" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                PDF
            </a>
            <button type="button" onclick="editTimesheet({{ $timesheet->id }}, null)" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</button>
            <button type="button" onclick="confirmDelete('{{ route("timesheets.destroy", $timesheet) }}', null, '{{ route("timesheets.index") }}')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
        </div>
    </div>

    <!-- Main Details Card -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div>
                <h2 class="text-2xl font-bold mb-6">Timesheet Details</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date</label>
                        <p class="text-lg text-gray-900 mt-1">{{ $timesheet->date->format('M d, Y') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Employee</label>
                        <p class="text-lg text-gray-900 mt-1">{{ $timesheet->employee->first_name }} {{ $timesheet->employee->last_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Project</label>
                        <p class="text-lg text-gray-900 mt-1">{{ $timesheet->project->name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phase code</label>
                        <p class="text-lg text-gray-900 mt-1">{{ $timesheet->costCode?->code ?? '—' }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Crew</label>
                        <p class="text-lg text-gray-900 mt-1">{{ $timesheet->crew->name ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Shift</label>
                        <p class="text-lg text-gray-900 mt-1">{{ $timesheet->shift?->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <h2 class="text-2xl font-bold mb-6">Hours & Status</h2>

                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <label class="font-medium text-gray-700">Regular Hours</label>
                        <p class="text-lg text-gray-900 font-semibold">{{ $timesheet->regular_hours }}</p>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <label class="font-medium text-gray-700">Overtime Hours</label>
                        <p class="text-lg text-gray-900 font-semibold">{{ $timesheet->overtime_hours }}</p>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <label class="font-medium text-gray-700">Double Time Hours</label>
                        <p class="text-lg text-gray-900 font-semibold">{{ $timesheet->double_time_hours }}</p>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-blue-50 rounded border-2 border-blue-200">
                        <label class="font-medium text-gray-700">Total Hours</label>
                        <p class="text-lg text-blue-900 font-bold">{{ $timesheet->total_hours }}</p>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <label class="font-medium text-gray-700">Cost</label>
                        <p class="text-lg text-gray-900 font-semibold">${{ number_format($timesheet->total_cost, 2) }}</p>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <label class="font-medium text-gray-700">Billable</label>
                        <p class="text-lg text-gray-900 font-semibold">
                            @if ((float) ($timesheet->billable_amount ?? 0) > 0)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Yes</span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">No</span>
                            @endif
                        </p>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <label class="font-medium text-gray-700">Status</label>
                        <p class="text-lg font-semibold">
                            @php
                                $statusColors = [
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'submitted' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                ];
                                $statusClass = $statusColors[$timesheet->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusClass }}">
                                {{ ucfirst($timesheet->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if ($timesheet->notes)
            <div class="mt-8 pt-8 border-t">
                <h3 class="text-lg font-semibold mb-3">Notes</h3>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $timesheet->notes }}</p>
            </div>
        @endif
    </div>

    <!-- Action Buttons -->
    @if (in_array($timesheet->status, ['draft', 'submitted']))
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Actions</h3>
            <div class="flex gap-4">
                @if ($timesheet->status === 'submitted' || $timesheet->status === 'draft')
                    <form method="POST" action="{{ route('timesheets.approve', $timesheet) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                            Approve
                        </button>
                    </form>
                @endif

                @if ($timesheet->status === 'submitted' || $timesheet->status === 'draft')
                    <form method="POST" action="{{ route('timesheets.reject', $timesheet) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">
                            Reject
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <!-- Cost Allocation Section -->
    <div class="bg-white rounded-lg shadow p-6 mt-6">
        <h3 class="text-lg font-semibold mb-4">Cost allocation</h3>
        @if($timesheet->costAllocations->isNotEmpty())
            <table class="w-full text-sm border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-3 py-2 border-b">Code</th>
                        <th class="text-right px-3 py-2 border-b">Hours</th>
                        <th class="text-right px-3 py-2 border-b">Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($timesheet->costAllocations as $alloc)
                        <tr>
                            <td class="px-3 py-2 border-b">{{ $alloc->costCode?->code ?? '—' }}</td>
                            <td class="px-3 py-2 border-b text-right">{{ number_format((float) $alloc->hours, 2) }}</td>
                            <td class="px-3 py-2 border-b text-right">${{ number_format((float) $alloc->cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-600">No phase code assigned. Edit the timesheet and choose a phase code to allocate labor.</p>
        @endif
    </div>
</div>

@include('timesheets.partials.timesheet-edit-modal')
@endsection

@push('scripts')
@include('timesheets.partials.timesheet-edit-script')
@endpush
