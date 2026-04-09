@extends('layouts.app')

@section('title', $employee->first_name . ' ' . $employee->last_name)

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $employee->full_name }}</h1>
            <p class="text-gray-600 mt-1">
                Employee #{{ $employee->employee_number }}
                @if($employee->legacy_employee_id)
                    <span class="ml-2 text-xs text-gray-500">Legacy ID: <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">{{ $employee->legacy_employee_id }}</span></span>
                @endif
            </p>
        </div>
        <a href="{{ route('employees.edit', $employee) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
            Edit
        </a>
    </div>

    <!-- Employee Header Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Info Card -->
        <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 border-b pb-2">Employee Information</h2>

            <div>
                <p class="text-sm text-gray-600">Role</p>
                <p class="text-lg font-semibold text-gray-900">{{ ucfirst($employee->role ?? 'N/A') }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-600">Craft</p>
                <p class="text-lg font-semibold text-gray-900">{{ $employee->craft?->name ?? 'N/A' }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-600">Status</p>
                @php
                    $statusClasses = [
                        'active' => 'bg-green-100 text-green-800',
                        'inactive' => 'bg-gray-100 text-gray-800',
                        'on_leave' => 'bg-yellow-100 text-yellow-800',
                    ];
                @endphp
                <span class="inline-block mt-1 px-3 py-1 rounded-full text-sm font-medium {{ $statusClasses[$employee->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucwords(str_replace('_', ' ', $employee->status)) }}
                </span>
            </div>

            <div>
                <p class="text-sm text-gray-600">Hire Date</p>
                <p class="text-lg font-semibold text-gray-900">{{ $employee->hire_date?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Contact Card -->
        <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 border-b pb-2">Contact Information</h2>

            <div>
                <p class="text-sm text-gray-600">Email</p>
                <p class="text-gray-900">
                    <a href="mailto:{{ $employee->email }}" class="text-blue-600 hover:text-blue-900">{{ $employee->email ?? 'N/A' }}</a>
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-600">Phone</p>
                <p class="text-gray-900">
                    <a href="tel:{{ $employee->phone }}" class="text-blue-600 hover:text-blue-900">{{ $employee->phone ?? 'N/A' }}</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Rates Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 border-b pb-4 mb-4">Rates</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Hourly Rate</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($employee->hourly_rate, 2) }}</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Overtime Rate</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($employee->overtime_rate, 2) }}</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Billable Rate</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($employee->billable_rate, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 mb-2">Total Hours This Month</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalHoursThisMonth ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-2">Based on submitted timesheets</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 mb-2">Total Hours This Year</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalHoursThisYear ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-2">Based on submitted timesheets</p>
        </div>
    </div>

    <!-- Recent Timesheets -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 border-b pb-4 mb-4">Recent Timesheets</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Project</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700">Hours</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700">Cost</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($employee->timesheets as $timesheet)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-900">{{ $timesheet->date?->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $timesheet->project?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 text-right text-gray-900">{{ number_format($timesheet->total_hours ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-900">${{ number_format($timesheet->total_cost ?? 0, 2) }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    {{ ucfirst($timesheet->status ?? 'submitted') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">No timesheets found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
