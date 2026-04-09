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

    <!-- Certifications & Training -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Certifications & Training</h2>
            <button type="button" onclick="openModal('addCertModal')" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Certification
            </button>
        </div>

        @if($employee->certifications->isEmpty())
            <p class="text-sm text-gray-400 py-4 text-center">No certifications on file.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b"><tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Name</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Cert #</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Issuing Authority</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Issued</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Expires</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">Status</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600" width="80">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($employee->certifications->sortBy('expiry_date') as $cert)
                            @php
                                $statusBadge = match($cert->status) {
                                    'expired' => 'bg-red-100 text-red-700',
                                    'expiring_soon' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-green-100 text-green-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-3 py-2 font-medium">{{ $cert->name }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $cert->certification_number ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $cert->issuing_authority ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $cert->issue_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $cert->expiry_date?->format('M j, Y') ?? 'N/A' }}</td>
                                <td class="px-3 py-2 text-center"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadge }}">{{ ucwords(str_replace('_', ' ', $cert->status)) }}</span></td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        @if($cert->file_path)
                                            <a href="{{ route('certifications.download', $cert) }}" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-blue-600 hover:bg-blue-50" title="Download">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            </a>
                                        @endif
                                        <button type="button" onclick="confirmDelete('{{ route('employees.certifications.destroy', [$employee, $cert]) }}')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Add Certification Modal -->
    <div id="addCertModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('addCertModal')">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Add Certification</h3>
                <button type="button" onclick="closeModal('addCertModal')" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form id="certForm" class="p-6 space-y-4" enctype="multipart/form-data">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Certification Name *</label><input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="e.g. OSHA 10"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Cert Number</label><input type="text" name="certification_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Issuing Authority</label><input type="text" name="issuing_authority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="e.g. OSHA, NCCER, NCCCO"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Issue Date</label><input type="date" name="issue_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label><input type="date" name="expiry_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Notes</label><textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"></textarea></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Certificate File <span class="text-xs text-gray-400">(PDF, image — max 10MB)</span></label><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2"></div>
            </form>
            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                <button type="button" onclick="closeModal('addCertModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="button" onclick="submitCert()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Certification</button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function submitCert() {
        var formData = new FormData(document.getElementById('certForm'));
        $.ajax({
            url: '{{ route("employees.certifications.store", $employee) }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                closeModal('addCertModal');
                Swal.fire({ icon: 'success', title: 'Saved', text: res.message, timer: 2000, showConfirmButton: false });
                setTimeout(function() { location.reload(); }, 1500);
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'Save failed.';
                if (xhr.responseJSON?.errors) msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            }
        });
    }
    </script>
    @endpush

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
