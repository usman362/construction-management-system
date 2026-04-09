@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">Edit Employee</h1>
        <a href="{{ route('employees.show', $employee) }}" class="text-blue-600 hover:text-blue-800">&larr; Back</a>
    </div>

    <form action="{{ route('employees.update', $employee) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        @include('employees.partials.form-fields', ['employee' => $employee])

        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">Update Employee</button>
            <a href="{{ route('employees.show', $employee) }}" class="bg-gray-400 text-white px-6 py-2 rounded-lg hover:bg-gray-500 transition font-medium">Cancel</a>
        </div>
    </form>
</div>
@endsection
