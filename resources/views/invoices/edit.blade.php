@extends('layouts.app')

@section('title', 'Edit Invoice')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Invoices</a>
    </div>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Edit Invoice</h1>

        <form method="POST" action="{{ route('invoices.update', $invoice) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">Project *</label>
                <select name="project_id" id="project_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('project_id') border-red-500 @enderror">
                    <option value="">Select Project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ $invoice->project_id == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('project_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="vendor_id" class="block text-sm font-medium text-gray-700 mb-2">Vendor *</label>
                <select name="vendor_id" id="vendor_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('vendor_id') border-red-500 @enderror">
                    <option value="">Select Vendor</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ $invoice->vendor_id == $vendor->id ? 'selected' : '' }}>
                            {{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
                @error('vendor_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="commitment_id" class="block text-sm font-medium text-gray-700 mb-2">Commitment (Optional)</label>
                <select name="commitment_id" id="commitment_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                    <option value="">Select Commitment</option>
                    @foreach ($commitments as $commitment)
                        <option value="{{ $commitment->id }}" {{ $invoice->commitment_id == $commitment->id ? 'selected' : '' }}>
                            {{ $commitment->commitment_number }} - {{ $commitment->description }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="cost_code_id" class="block text-sm font-medium text-gray-700 mb-2">Cost Code *</label>
                <select name="cost_code_id" id="cost_code_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('cost_code_id') border-red-500 @enderror">
                    <option value="">Select Cost Code</option>
                    @foreach ($costCodes as $code)
                        <option value="{{ $code->id }}" {{ $invoice->cost_code_id == $code->id ? 'selected' : '' }}>
                            {{ $code->code }} - {{ $code->name }}
                        </option>
                    @endforeach
                </select>
                @error('cost_code_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="invoice_number" class="block text-sm font-medium text-gray-700 mb-2">Invoice Number *</label>
                <input type="text" name="invoice_number" id="invoice_number" required value="{{ $invoice->invoice_number }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('invoice_number') border-red-500 @enderror">
                @error('invoice_number')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <textarea name="description" id="description" rows="4" required class="w-full border-gray-300 rounded-lg shadow-sm @error('description') border-red-500 @enderror">{{ $invoice->description }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount *</label>
                <input type="number" name="amount" id="amount" step="0.01" required value="{{ $invoice->amount }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('amount') border-red-500 @enderror">
                @error('amount')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="invoice_date" class="block text-sm font-medium text-gray-700 mb-2">Invoice Date *</label>
                <input type="date" name="invoice_date" id="invoice_date" required value="{{ $invoice->invoice_date->format('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('invoice_date') border-red-500 @enderror">
                @error('invoice_date')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="due_date" class="block text-sm font-medium text-gray-700 mb-2">Due Date *</label>
                <input type="date" name="due_date" id="due_date" required value="{{ $invoice->due_date->format('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('due_date') border-red-500 @enderror">
                @error('due_date')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Update Invoice
                </button>
                <a href="{{ route('invoices.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
