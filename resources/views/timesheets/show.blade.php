@extends('layouts.app')

@section('title', 'Timesheet Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('timesheets.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Timesheets</a>
        <div class="space-x-2">
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
                        <label class="block text-sm font-medium text-gray-700">Cost code</label>
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
            <p class="text-gray-600">No cost code assigned. Edit the timesheet and choose a cost code to allocate labor.</p>
        @endif
    </div>
</div>

@include('timesheets.partials.timesheet-edit-modal')
@endsection

@push('scripts')
@include('timesheets.partials.timesheet-edit-script')
@endpush
