@extends('layouts.app')

@section('title', 'Edit Phase Code')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('cost-codes.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Phase Codes</a>
    </div>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Edit Phase Code</h1>

        <form method="POST" action="{{ route('cost-codes.update', $costCode) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Phase Code # *</label>
                <input type="text" name="code" id="code" required value="{{ $costCode->code }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('code') border-red-500 @enderror">
                @error('code')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Phase Code Name *</label>
                <input type="text" name="name" id="name" required value="{{ $costCode->name }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="cost_type_id" class="block text-sm font-medium text-gray-700 mb-2">Cost Type</label>
                <select name="cost_type_id" id="cost_type_id" class="w-full border-gray-300 rounded-lg shadow-sm @error('cost_type_id') border-red-500 @enderror">
                    <option value="">— None —</option>
                    @foreach ($costTypes ?? [] as $ct)
                        <option value="{{ $ct->id }}" {{ $costCode->cost_type_id == $ct->id ? 'selected' : '' }}>
                            {{ $ct->code }} — {{ $ct->name }}
                        </option>
                    @endforeach
                </select>
                @error('cost_type_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ $costCode->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm">
                    <span class="ml-2 text-sm font-medium text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Update Phase Code
                </button>
                <a href="{{ route('cost-codes.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
