@extends('layouts.app')

@section('title', 'Edit Cost Code')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('cost-codes.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Cost Codes</a>
    </div>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Edit Cost Code</h1>

        <form method="POST" action="{{ route('cost-codes.update', $costCode) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Code *</label>
                <input type="text" name="code" id="code" required value="{{ $costCode->code }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('code') border-red-500 @enderror">
                @error('code')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                <input type="text" name="name" id="name" required value="{{ $costCode->name }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm @error('description') border-red-500 @enderror">{{ $costCode->description }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">Parent Cost Code (Optional)</label>
                <select name="parent_id" id="parent_id" class="w-full border-gray-300 rounded-lg shadow-sm @error('parent_id') border-red-500 @enderror">
                    <option value="">No Parent</option>
                    @foreach ($parentCodes ?? [] as $parentCode)
                        @if ($parentCode->id !== $costCode->id)
                            <option value="{{ $parentCode->id }}" {{ $costCode->parent_id == $parentCode->id ? 'selected' : '' }}>
                                {{ $parentCode->code }} - {{ $parentCode->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
                @error('parent_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                <input type="number" name="sort_order" id="sort_order" value="{{ $costCode->sort_order }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('sort_order') border-red-500 @enderror">
                @error('sort_order')
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
                    Update Cost Code
                </button>
                <a href="{{ route('cost-codes.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
