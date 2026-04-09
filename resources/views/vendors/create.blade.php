@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Create Vendor</h1>

        <form method="POST" action="{{ route('vendors.store') }}" class="space-y-8">
            @csrf

            <!-- Basic Information -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="vendor_code" class="block text-sm font-semibold text-gray-700 mb-2">Legacy Vendor Code</label>
                    <input type="text" name="vendor_code" id="vendor_code" value="{{ old('vendor_code') }}" placeholder="From legacy system" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('vendor_code') border-red-500 @enderror">
                    @error('vendor_code')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Vendor Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contact_name" class="block text-sm font-semibold text-gray-700 mb-2">Contact Name</label>
                    <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('contact_name') border-red-500 @enderror">
                    @error('contact_name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Address Information -->
            <div class="border-t pt-6">
                <h3 class="font-semibold text-gray-800 mb-4">Address</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">Street Address</label>
                        <input type="text" name="address" id="address" value="{{ old('address') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('address') border-red-500 @enderror">
                        @error('address')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">City</label>
                        <input type="text" name="city" id="city" value="{{ old('city') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('city') border-red-500 @enderror">
                        @error('city')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="state" class="block text-sm font-semibold text-gray-700 mb-2">State</label>
                        <input type="text" name="state" id="state" value="{{ old('state') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('state') border-red-500 @enderror">
                        @error('state')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="zip" class="block text-sm font-semibold text-gray-700 mb-2">ZIP Code</label>
                        <input type="text" name="zip" id="zip" value="{{ old('zip') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('zip') border-red-500 @enderror">
                        @error('zip')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Type -->
            <div>
                <label for="type" class="block text-sm font-semibold text-gray-700 mb-2">Vendor Type *</label>
                <select name="type" id="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('type') border-red-500 @enderror">
                    <option value="">Select a type</option>
                    <option value="equipment" {{ old('type') === 'equipment' ? 'selected' : '' }}>Equipment</option>
                    <option value="subcontractor" {{ old('type') === 'subcontractor' ? 'selected' : '' }}>Subcontractor</option>
                    <option value="supplier" {{ old('type') === 'supplier' ? 'selected' : '' }}>Supplier</option>
                    <option value="service" {{ old('type') === 'service' ? 'selected' : '' }}>Service Provider</option>
                </select>
                @error('type')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded">
                    Create Vendor
                </button>
                <a href="{{ route('vendors.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-8 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
