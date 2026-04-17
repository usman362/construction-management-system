@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Settings</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- LEFT COLUMN: Profile -->
        <div class="space-y-6">
            <!-- Profile Info Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    <div>
                        <h2 class="text-base font-semibold text-gray-800">Profile Information</h2>
                        <p class="text-xs text-gray-500">Update your name and email address</p>
                    </div>
                </div>
                <form id="profileForm" class="p-6 space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                        <div class="w-14 h-14 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-white text-xl font-bold">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-gray-900">{{ $user->name }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                                    @switch($user->role)
                                        @case('admin') bg-red-100 text-red-700 @break
                                        @case('project_manager') bg-blue-100 text-blue-700 @break
                                        @case('accountant') bg-green-100 text-green-700 @break
                                        @case('field') bg-amber-100 text-amber-700 @break
                                        @default bg-gray-100 text-gray-700
                                    @endswitch
                                ">{{ $user->role_label }}</span>
                                <span class="text-[10px] text-gray-400">since {{ $user->created_at?->format('M Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Full Name</label>
                        <input type="text" name="name" value="{{ $user->name }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email Address</label>
                        <input type="email" name="email" value="{{ $user->email }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="button" onclick="saveProfile()" class="px-5 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Change Password Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    <div>
                        <h2 class="text-base font-semibold text-gray-800">Change Password</h2>
                        <p class="text-xs text-gray-500">Ensure your account is using a strong password</p>
                    </div>
                </div>
                <form id="passwordForm" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">New Password</label>
                        <input type="password" name="password" id="new_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required minlength="8">
                        <p class="text-[10px] text-gray-400 mt-1">Minimum 8 characters</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Confirm New Password</label>
                        <input type="password" name="password_confirmation" id="confirm_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required minlength="8">
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="button" onclick="changePassword()" class="px-5 py-2 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Change Password</button>
                    </div>
                </form>
            </div>

            <!-- Account Info Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                    <h2 class="text-base font-semibold text-gray-800">Account Information</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-500">Role</p>
                            <p class="font-medium text-gray-900">{{ $user->role_label }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Status</p>
                            <p class="font-medium {{ $user->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Created</p>
                            <p class="font-medium text-gray-900 text-xs">{{ $user->created_at?->format('M j, Y g:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Updated</p>
                            <p class="font-medium text-gray-900 text-xs">{{ $user->updated_at?->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Website Settings (Admin only) -->
        @if(Auth::user()->role === 'admin')
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <div>
                        <h2 class="text-base font-semibold text-gray-800">Website Settings</h2>
                        <p class="text-xs text-gray-500">Customize branding, logo, and favicon</p>
                    </div>
                </div>
                <form id="settingsForm" class="p-6 space-y-5" enctype="multipart/form-data">
                    <!-- Company Name -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Company Name *</label>
                        <input type="text" name="company_name" value="{{ $settings['company_name'] }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    </div>

                    <!-- Company Tagline -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tagline</label>
                        <input type="text" name="company_tagline" value="{{ $settings['company_tagline'] }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="e.g. Construction Management">
                    </div>

                    <!-- Company Logo -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-2">Company Logo</label>
                        <div class="flex items-start gap-4">
                            <div class="w-20 h-20 bg-gray-100 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center flex-shrink-0 overflow-hidden" id="logoPreview">
                                @if($settings['company_logo'])
                                    <img src="{{ asset($settings['company_logo']) }}" class="w-full h-full object-contain" alt="Logo">
                                @else
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 002.25-2.25V5.25a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1">
                                <label class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg cursor-pointer hover:bg-blue-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                    Upload Logo
                                    <input type="file" name="company_logo" accept="image/*" class="hidden" onchange="previewFile(this, 'logoPreview')">
                                </label>
                                @if($settings['company_logo'])
                                <button type="button" onclick="markRemove('logo')" class="ml-2 text-xs text-red-500 hover:text-red-700">Remove</button>
                                @endif
                                <p class="text-[10px] text-gray-400 mt-1.5">PNG, JPG, SVG. Max 2MB. If empty, initials will be shown.</p>
                                <input type="hidden" name="remove_logo" id="remove_logo" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- Favicon -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-2">Favicon</label>
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden" id="faviconPreview">
                                @if($settings['favicon'])
                                    <img src="{{ asset($settings['favicon']) }}" class="w-full h-full object-contain" alt="Favicon">
                                @else
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                                @endif
                            </div>
                            <div class="flex-1">
                                <label class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg cursor-pointer hover:bg-blue-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                    Upload Favicon
                                    <input type="file" name="favicon" accept="image/*,.ico" class="hidden" onchange="previewFile(this, 'faviconPreview')">
                                </label>
                                @if($settings['favicon'])
                                <button type="button" onclick="markRemove('favicon')" class="ml-2 text-xs text-red-500 hover:text-red-700">Remove</button>
                                @endif
                                <p class="text-[10px] text-gray-400 mt-1.5">ICO, PNG. 32x32 or 64x64 recommended.</p>
                                <input type="hidden" name="remove_favicon" id="remove_favicon" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- Primary Color -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Primary Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="primary_color" value="{{ $settings['primary_color'] ?? '#2563eb' }}" class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                            <input type="text" id="colorHex" value="{{ $settings['primary_color'] ?? '#2563eb' }}" class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" readonly>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="bg-gray-900 rounded-xl p-4 mt-2">
                        <p class="text-[10px] text-gray-500 uppercase font-semibold mb-3 tracking-wider">Sidebar Preview</p>
                        <div class="flex items-center gap-3">
                            <div id="previewLogoArea" class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold text-white flex-shrink-0 overflow-hidden" style="background-color: {{ $settings['primary_color'] ?? '#2563eb' }}">
                                @if($settings['company_logo'])
                                    <img src="{{ $settings['company_logo'] }}" class="w-full h-full object-contain" alt="Preview">
                                @else
                                    {{ strtoupper(substr($settings['company_name'] ?? 'BT', 0, 2)) }}
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white leading-tight" id="previewName">{{ $settings['company_name'] ?? 'BuildTrack' }}</p>
                                <p class="text-[11px] text-gray-500 leading-tight" id="previewTagline">{{ $settings['company_tagline'] ?? 'Construction Mgmt' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="button" onclick="saveSettings()" class="px-5 py-2 text-xs font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition">Save Website Settings</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
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
                Swal.fire({icon: 'error', title: 'Validation Error', html: Object.values(errors).flat().join('<br>'), confirmButtonColor: '#2563eb'});
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
        Swal.fire({icon: 'error', title: 'Error', text: 'Passwords do not match.', confirmButtonColor: '#2563eb'});
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
                Swal.fire({icon: 'error', title: 'Validation Error', html: Object.values(errors).flat().join('<br>'), confirmButtonColor: '#2563eb'});
            } else {
                Toast.fire({icon: 'error', title: xhr.responseJSON?.message || 'Error changing password'});
            }
        }
    });
}

function saveSettings() {
    var form = document.getElementById('settingsForm');
    var formData = new FormData(form);

    $.ajax({
        url: '{{ route("settings.update") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: { 'X-HTTP-Method-Override': 'PUT' },
        success: function(res) {
            Toast.fire({icon: 'success', title: res.message || 'Settings saved!'});
            setTimeout(function() { window.location.reload(); }, 1500);
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors;
            if (errors) {
                Swal.fire({icon: 'error', title: 'Validation Error', html: Object.values(errors).flat().join('<br>'), confirmButtonColor: '#7c3aed'});
            } else {
                Toast.fire({icon: 'error', title: xhr.responseJSON?.message || 'Error saving settings'});
            }
        }
    });
}

function previewFile(input, targetId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(targetId).innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-contain" alt="Preview">';
            if (targetId === 'logoPreview') {
                document.getElementById('previewLogoArea').innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-contain" alt="Preview">';
                document.getElementById('remove_logo').value = '0';
            }
            if (targetId === 'faviconPreview') {
                document.getElementById('remove_favicon').value = '0';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function markRemove(type) {
    if (type === 'logo') {
        document.getElementById('remove_logo').value = '1';
        document.getElementById('logoPreview').innerHTML = '<svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 002.25-2.25V5.25a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 003.75 21z"/></svg>';
        var nameVal = document.querySelector('[name="company_name"]').value || 'BT';
        document.getElementById('previewLogoArea').innerHTML = nameVal.substring(0, 2).toUpperCase();
    } else {
        document.getElementById('remove_favicon').value = '1';
        document.getElementById('faviconPreview').innerHTML = '<svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>';
    }
}

// Live preview updates
document.querySelector('[name="company_name"]')?.addEventListener('input', function() {
    document.getElementById('previewName').textContent = this.value || 'BuildTrack';
    if (!document.querySelector('[name="company_logo"]').files.length && document.getElementById('remove_logo').value === '1') {
        document.getElementById('previewLogoArea').textContent = (this.value || 'BT').substring(0, 2).toUpperCase();
    }
});
document.querySelector('[name="company_tagline"]')?.addEventListener('input', function() {
    document.getElementById('previewTagline').textContent = this.value || 'Construction Mgmt';
});
document.querySelector('[name="primary_color"]')?.addEventListener('input', function() {
    document.getElementById('previewLogoArea').style.backgroundColor = this.value;
    document.getElementById('colorHex').value = this.value;
});
</script>
@endpush

@endsection
