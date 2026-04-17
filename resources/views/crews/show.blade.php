@extends('layouts.app')

@section('title', $crew->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">{{ $crew->name }}</h1>
        <button type="button" onclick="editCrew({{ $crew->id }}, null)" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
            Edit
        </button>
    </div>

    <!-- Crew Details Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 border-b pb-4 mb-4">Crew Details</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600">Project</p>
                <p class="text-lg font-semibold text-gray-900">{{ $crew->project?->name ?? 'N/A' }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-600">Foreman</p>
                <p class="text-lg font-semibold text-gray-900">{{ $crew->foreman?->first_name }} {{ $crew->foreman?->last_name ?? 'N/A' }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-600">Shift</p>
                <p class="text-lg font-semibold text-gray-900">{{ $crew->shift?->name ?? 'N/A' }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-600">Status</p>
                <span class="inline-block mt-1 px-3 py-1 rounded-full text-sm font-medium {{ $crew->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $crew->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Members Section -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Crew Members ({{ $crew->members_count ?? 0 }})</h2>
            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                Add Member
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Employee #</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Name</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Craft</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Assigned Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($crew->members ?? [] as $member)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-900 font-medium">{{ $member->employee_number }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $member->first_name }} {{ $member->last_name }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $member->craft?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $member->pivot?->assigned_date?->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="px-4 py-2">
                                <button type="button"
                                        onclick="confirmDelete('{{ route('crews.remove-member', [$crew, $member]) }}', null, window.location.href)"
                                        class="text-red-600 hover:text-red-900">Remove</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">No members assigned yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Member Form (Modal-like) -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 border-b pb-4 mb-4">Add Member to Crew</h2>

        @php
            $assignedIds = collect($crew->members ?? [])->pluck('employee_id')->all();
            $availableEmployees = collect($employees ?? [])->reject(fn($e) => in_array($e->id, $assignedIds));
        @endphp

        <form id="addMemberForm" action="{{ route('crews.add-member', $crew) }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Employee</label>
                <select
                    name="employee_id"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">-- Choose an employee --</option>
                    @forelse($availableEmployees as $emp)
                        <option value="{{ $emp->id }}">
                            {{ $emp->employee_number }} — {{ $emp->first_name }} {{ $emp->last_name }}@if($emp->craft) ({{ $emp->craft->name }})@endif
                        </option>
                    @empty
                        <option value="" disabled>No available employees to add</option>
                    @endforelse
                </select>
            </div>

            <div>
                <button
                    type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
                >
                    Add Member
                </button>
            </div>
        </form>
    </div>
</div>

@include('crews.partials.crew-edit-modal')
@endsection

@push('scripts')
@include('crews.partials.crew-edit-script')
<script>
$('#addMemberForm').on('submit', function(e){
    e.preventDefault();
    var form = this;
    $.ajax({
        url: form.action,
        type: 'POST',
        data: $(form).serialize(),
        success: function(res){
            Toast.fire({icon:'success', title: res.message || 'Member added'});
            setTimeout(() => window.location.reload(), 700);
        },
        error: function(xhr){
            Toast.fire({icon:'error', title: xhr.responseJSON?.message || 'Could not add member'});
        }
    });
});
</script>
@endpush
