@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-lg p-8 mb-8 text-white">
        <h2 class="text-3xl font-bold mb-2">Welcome to BuildTrack</h2>
        <p class="text-blue-100">{{ now()->format('l, F j, Y') }} - Manage your construction projects with ease</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Active Projects -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Active Projects</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['activeProjects'] ?? 0 }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Employees -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Employees</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['totalEmployees'] ?? 0 }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292m0-5.292a4 4 0 100 5.292m0-5.292A4.353 4.353 0 005.364 9M9 9H5.364m0 0a4 4 0 010 5.292m13.5-2.646a4 4 0 010-5.292m0 5.292A4.353 4.353 0 0118.636 9m-13.272 0h13.272"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending Timesheets -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Pending Timesheets</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['pendingTimesheets'] ?? 0 }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Open Change Orders -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Open Change Orders</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['openChangeOrders'] ?? 0 }}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Projects Table -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Recent Projects</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Project</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Budget</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">% Committed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Profit Margin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentProjects ?? [] as $project)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="font-medium text-gray-900">{{ $project->name ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $project->client?->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(($project->status ?? null) === 'active')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                @elseif(($project->status ?? null) === 'completed')
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Completed</span>
                                @else
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($project->status ?? 'Unknown') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">${{ number_format($project->budget ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($project->committed_percentage ?? 0) }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ ($project->committed_percentage ?? 0) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-medium {{ ($project->profit_margin ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ ($project->profit_margin ?? 0) >= 0 ? '+' : '' }}{{ number_format($project->profit_margin ?? 0, 1) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition" title="View">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">No projects found. Create your first project to get started.</td>
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
        window.location.href = '/projects/' + projectId + '/change-orders';
    }
    </script>
@endsection
