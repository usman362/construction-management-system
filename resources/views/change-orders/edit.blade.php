@extends('layouts.app')

@section('title', 'Edit Change Order')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('projects.change-orders.index', $project) }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Change Orders</a>
    </div>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Edit Change Order - {{ $project->name }}</h1>

        <form method="POST" action="{{ route('projects.change-orders.update', [$project, $changeOrder]) }}">
            @csrf
            @method('PUT')

            <!-- Change Order Info Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Change Order Info</h2>

                <div class="mb-4">
                    <label for="co_number" class="block text-sm font-medium text-gray-700 mb-2">Change Order Number *</label>
                    <input type="text" name="co_number" id="co_number" required value="{{ $changeOrder->co_number }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('co_number') border-red-500 @enderror">
                    @error('co_number')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                    <input type="date" name="date" id="date" required value="{{ $changeOrder->date->format('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('date') border-red-500 @enderror">
                    @error('date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description of Work Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Description of Work</h2>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea name="description" id="description" rows="4" required class="w-full border-gray-300 rounded-lg shadow-sm @error('description') border-red-500 @enderror">{{ $changeOrder->description }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="scope_of_work" class="block text-sm font-medium text-gray-700 mb-2">Scope of Work *</label>
                    <textarea name="scope_of_work" id="scope_of_work" rows="4" required class="w-full border-gray-300 rounded-lg shadow-sm @error('scope_of_work') border-red-500 @enderror">{{ $changeOrder->scope_of_work }}</textarea>
                    @error('scope_of_work')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Contract Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Contract</h2>

                <div class="mb-4">
                    <label for="contract_time_change_days" class="block text-sm font-medium text-gray-700 mb-2">Contract Time Change (Days)</label>
                    <input type="number" name="contract_time_change_days" id="contract_time_change_days" value="{{ $changeOrder->contract_time_change_days }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('contract_time_change_days') border-red-500 @enderror">
                    @error('contract_time_change_days')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="new_completion_date" class="block text-sm font-medium text-gray-700 mb-2">New Completion Date</label>
                    <input type="date" name="new_completion_date" id="new_completion_date" value="{{ $changeOrder->new_completion_date?->format('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('new_completion_date') border-red-500 @enderror">
                    @error('new_completion_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount *</label>
                    <input type="number" name="amount" id="amount" step="0.01" required value="{{ $changeOrder->amount }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('amount') border-red-500 @enderror">
                    @error('amount')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Update Change Order
                </button>
                <a href="{{ route('projects.change-orders.index', $project) }}" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
