@extends('layouts.app')

@section('title', 'New Employee')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">New Employee</h1>
        <a href="{{ route('employees.index') }}" class="text-blue-600 hover:text-blue-800">&larr; Back to Employees</a>
    </div>

    <form action="{{ route('employees.store') }}" method="POST" class="space-y-6">
        @csrf

        @include('employees.partials.form-fields', ['employee' => null])

        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">Create Employee</button>
            <a href="{{ route('employees.index') }}" class="bg-gray-400 text-white px-6 py-2 rounded-lg hover:bg-gray-500 transition font-medium">Cancel</a>
        </div>
    </form>
</div>
@endsection
