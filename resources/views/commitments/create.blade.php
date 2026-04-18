@extends('layouts.app')

@section('title', 'Create Commitment')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('projects.commitments.index', $project) }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Commitments</a>
    </div>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Create Commitment - {{ $project->name }}</h1>

        <form method="POST" action="{{ route('projects.commitments.store', $project) }}">
            @csrf

            <div class="mb-4">
                <label for="vendor_id" class="block text-sm font-medium text-gray-700 mb-2">Vendor *</label>
                <select name="vendor_id" id="vendor_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('vendor_id') border-red-500 @enderror">
                    <option value="">Select Vendor</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                            {{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
                @error('vendor_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="cost_code_id" class="block text-sm font-medium text-gray-700 mb-2">Phase Code *</label>
                <select name="cost_code_id" id="cost_code_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('cost_code_id') border-red-500 @enderror">
                    <option value="">Select Phase Code</option>
                    @foreach ($costCodes as $code)
                        <option value="{{ $code->id }}" {{ old('cost_code_id') == $code->id ? 'selected' : '' }}>
                            {{ $code->code }} - {{ $code->name }}
                        </option>
                    @endforeach
                </select>
                @error('cost_code_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="commitment_number" class="block text-sm font-medium text-gray-700 mb-2">Commitment Number *</label>
                <input type="text" name="commitment_number" id="commitment_number" required value="{{ old('commitment_number') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('commitment_number') border-red-500 @enderror">
                @error('commitment_number')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <textarea name="description" id="description" rows="4" required class="w-full border-gray-300 rounded-lg shadow-sm @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount *</label>
                <input type="number" name="amount" id="amount" step="0.01" required value="{{ old('amount') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('amount') border-red-500 @enderror">
                @error('amount')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="committed_date" class="block text-sm font-medium text-gray-700 mb-2">Committed Date *</label>
                <input type="date" name="committed_date" id="committed_date" required value="{{ old('committed_date') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('committed_date') border-red-500 @enderror">
                @error('committed_date')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                <select name="status" id="status" required class="w-full border-gray-300 rounded-lg shadow-sm @error('status') border-red-500 @enderror">
                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="invoiced" {{ old('status') == 'invoiced' ? 'selected' : '' }}>Invoiced</option>
                </select>
                @error('status')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Create Commitment
                </button>
                <a href="{{ route('projects.commitments.index', $project) }}" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
