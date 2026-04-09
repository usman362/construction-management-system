@extends('layouts.app')
@section('title', 'Database Backups')
@section('content')

<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Database Backups</h1>
        <form method="POST" action="{{ route('admin.backup.create') }}" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Creating...';">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                Create Backup Now
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($backups->isEmpty())
            <div class="py-12 text-center text-gray-400">
                <p class="text-sm">No backups yet. Click "Create Backup Now" to create your first one.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b"><tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">File</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Size</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Created</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600" width="120">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($backups as $backup)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $backup['filename'] }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $backup['size_formatted'] }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $backup['created_at'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.backup.download', $backup['filename']) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Download</a>
                                    <form method="POST" action="{{ route('admin.backup.destroy', $backup['filename']) }}" onsubmit="return confirm('Delete this backup?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <p class="text-xs text-gray-400">Backups are stored in <code>storage/app/backups/</code>. Download important backups to a separate location for safekeeping.</p>
</div>
@endsection
