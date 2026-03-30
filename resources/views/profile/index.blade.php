@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">My Profile</h1>

    <!-- Profile Info Card -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Profile Information</h2>
            <p class="text-sm text-gray-500">Update your name and email address</p>
        </div>
        <form id="profileForm" class="p-6 space-y-4">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white text-2xl font-bold">{{ substr($user->name, 0, 1) }}</span>
                </div>
                <div>
                    <p class="text-lg font-semibold text-gray-900">{{ $user->name }}</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        @switch($user->role)
                            @case('admin') bg-red-100 text-red-800 @break
                            @case('project_manager') bg-blue-100 text-blue-800 @break
                            @case('accountant') bg-green-100 text-green-800 @break
                            @case('field') bg-amber-100 text-amber-800 @break
                            @default bg-gray-100 text-gray-800
                        @endswitch
                    ">{{ $user->role_label }}</span>
                    <span class="text-xs text-gray-400 ml-2">Member since {{ $user->created_at?->format('M j, Y') }}</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="name" value="{{ $user->name }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" value="{{ $user->email }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
            </div>
            <div class="flex justify-end pt-2">
                <button type="button" onclick="saveProfile()" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Change Password Card -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Change Password</h2>
            <p class="text-sm text-gray-500">Ensure your account is using a strong password</p>
        </div>
        <form id="passwordForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" name="current_password" id="current_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" name="password" id="new_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required minlength="8">
                <p class="text-xs text-gray-400 mt-1">Minimum 8 characters</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="confirm_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required minlength="8">
            </div>
            <div class="flex justify-end pt-2">
                <button type="button" onclick="changePassword()" class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Change Password</button>
            </div>
        </form>
    </div>

    <!-- Account Info Card (read-only) -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Account Information</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">Role</p>
                    <p class="font-medium text-gray-900">{{ $user->role_label }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Status</p>
                    <p class="font-medium {{ $user->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Account Created</p>
                    <p class="font-medium text-gray-900">{{ $user->created_at?->format('M j, Y g:i A') }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Last Updated</p>
                    <p class="font-medium text-gray-900">{{ $user->updated_at?->format('M j, Y g:i A') }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4">To change your role, please contact an administrator.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function saveProfile() {
    var form = document.getElementById('profileForm');
    var formData = new FormData(form);
    var data = {};
    formData.forEach(function(v, k) { data[k] = v; });

    $.ajax({
        url: '{{ route("profile.update") }}',
        type: 'PUT',
        data: data,
        success: function(res) {
            Toast.fire({icon: 'success', title: res.message || 'Profile updated!'});
            setTimeout(function() { window.location.reload(); }, 1500);
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors;
            if (errors) {
                var msg = Object.values(errors).flat().join('<br>');
                Swal.fire({icon: 'error', title: 'Validation Error', html: msg, confirmButtonColor: '#2563eb'});
            } else {
                Toast.fire({icon: 'error', title: xhr.responseJSON?.message || 'Error saving'});
            }
        }
    });
}

function changePassword() {
    var newPwd = document.getElementById('new_password').value;
    var confirmPwd = document.getElementById('confirm_password').value;

    if (newPwd !== confirmPwd) {
        Swal.fire({icon: 'error', title: 'Error', text: 'New password and confirmation do not match.', confirmButtonColor: '#2563eb'});
        return;
    }

    if (newPwd.length < 8) {
        Swal.fire({icon: 'error', title: 'Error', text: 'Password must be at least 8 characters.', confirmButtonColor: '#2563eb'});
        return;
    }

    var form = document.getElementById('passwordForm');
    var formData = new FormData(form);
    var data = {};
    formData.forEach(function(v, k) { data[k] = v; });

    $.ajax({
        url: '{{ route("profile.password") }}',
        type: 'PUT',
        data: data,
        success: function(res) {
            Toast.fire({icon: 'success', title: res.message || 'Password changed!'});
            form.reset();
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors;
            if (errors) {
                var msg = Object.values(errors).flat().join('<br>');
                Swal.fire({icon: 'error', title: 'Validation Error', html: msg, confirmButtonColor: '#2563eb'});
            } else {
                Toast.fire({icon: 'error', title: xhr.responseJSON?.message || 'Error changing password'});
            }
        }
    });
}
</script>
@endpush

@endsection
