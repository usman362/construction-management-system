@extends('layouts.app')

@section('title', $client->name)

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap justify-between items-center gap-4">
        <a href="{{ route('clients.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Clients</a>
        <div class="space-x-2">
            <button type="button" onclick="editClient({{ $client->id }})" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</button>
            <button type="button" onclick="confirmDelete('{{ route('clients.destroy', $client) }}', null, '{{ route('clients.index') }}')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
        </div>
    </div>

    <h1 class="text-3xl font-bold text-gray-900">{{ $client->name }}</h1>

    <!-- Client Information Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Details Card -->
        <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 border-b pb-4">Contact Information</h2>

            <div>
                <p class="text-sm text-gray-600">Contact Name</p>
                <p class="text-lg font-semibold text-gray-900">{{ $client->contact_name ?? 'N/A' }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-600">Email</p>
                <p class="text-gray-900">
                    <a href="mailto:{{ $client->email }}" class="text-blue-600 hover:text-blue-900">{{ $client->email ?? 'N/A' }}</a>
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-600">Phone</p>
                <p class="text-gray-900">
                    <a href="tel:{{ $client->phone }}" class="text-blue-600 hover:text-blue-900">{{ $client->phone ?? 'N/A' }}</a>
                </p>
            </div>
        </div>

        <!-- Address Card -->
        <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 border-b pb-4">Address</h2>

            <div>
                <p class="text-sm text-gray-600">Street Address</p>
                <p class="text-gray-900">{{ $client->address ?? 'N/A' }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">City</p>
                    <p class="text-gray-900">{{ $client->city ?? 'N/A' }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-600">State</p>
                    <p class="text-gray-900">{{ $client->state ?? 'N/A' }}</p>
                </div>
            </div>

            <div>
                <p class="text-sm text-gray-600">ZIP Code</p>
                <p class="text-gray-900">{{ $client->zip ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Projects Section -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 border-b pb-4 mb-4">Projects</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Project #</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Name</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Start Date</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700">Budget</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($client->projects ?? [] as $project)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-900 font-medium">{{ $project->project_number }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $project->name }}</td>
                            <td class="px-4 py-2">
                                @php
                                    $statusClasses = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'on_hold' => 'bg-yellow-100 text-yellow-800',
                                        'completed' => 'bg-blue-100 text-blue-800',
                                        'closed' => 'bg-gray-100 text-gray-800',
                                        'bidding' => 'bg-purple-100 text-purple-800',
                                        'awarded' => 'bg-orange-100 text-orange-800',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded text-xs font-medium {{ $statusClasses[$project->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucwords(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-700">{{ $project->start_date?->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 font-medium">${{ number_format($project->original_budget, 0) }}</td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition" title="View">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500">No projects found for this client.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('clients.partials.client-edit-modal')

@push('scripts')
<script>
var table = null;
function editClient(id){
    $.get('{{ url('/clients') }}/'+id+'/edit', function(d){
        let f=document.getElementById('editForm');
        f.querySelector('#edit_id').value=d.id;
        f.querySelector('[name="name"]').value=d.name;
        f.querySelector('[name="contact_name"]').value=d.contact_name||'';
        f.querySelector('[name="email"]').value=d.email||'';
        f.querySelector('[name="phone"]').value=d.phone||'';
        f.querySelector('[name="address"]').value=d.address||'';
        f.querySelector('[name="city"]').value=d.city||'';
        f.querySelector('[name="state"]').value=d.state||'';
        f.querySelector('[name="zip"]').value=d.zip||'';
        document.getElementById('editSaveBtn').onclick=function(){ submitForm('editForm','{{ url('/clients') }}/'+d.id,'PUT',table,'editModal'); };
        openModal('editModal');
    });
}
</script>
@endpush
@endsection
