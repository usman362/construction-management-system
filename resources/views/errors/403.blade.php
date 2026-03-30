@extends('layouts.app')

@section('title', 'Access Denied')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="text-center">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Access Denied</h1>
        <p class="text-gray-500 mb-6">You don't have permission to access this page.<br>Contact your administrator if you need access.</p>
        <div class="flex items-center justify-center gap-3">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Go to Dashboard</a>
            <a href="javascript:history.back()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Go Back</a>
        </div>
        <p class="mt-4 text-xs text-gray-400">Your role: <span class="font-medium">{{ Auth::user()->role_label ?? 'Unknown' }}</span></p>
    </div>
</div>
@endsection
