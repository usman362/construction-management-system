<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index', [
            'user' => Auth::user(),
            'settings' => [
                'company_name' => Setting::get('company_name', 'BuildTrack'),
                'company_tagline' => Setting::get('company_tagline', 'Construction Mgmt'),
                'company_logo' => Setting::get('company_logo'),
                'favicon' => Setting::get('favicon'),
                'primary_color' => Setting::get('primary_color', '#2563eb'),
                // Integrations — optional 3rd-party API keys
                'weather_api_key' => Setting::get('weather_api_key', ''),
            ],
        ]);
    }

    /**
     * Update profile details (name, email).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Profile updated successfully']);
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        // 2026-05-01 (Brenda): "password it way too easy" — bumped policy
        // from min:8 to a real strength requirement: 12+ chars, mixed case,
        // numbers, and a special char. Uses Laravel's Password rule so the
        // error messages name each missing requirement.
        $request->validate([
            'current_password' => 'required',
            'password' => [
                'required',
                'string',
                'confirmed',
                \Illuminate\Validation\Rules\Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => ['current_password' => ['The current password is incorrect.']],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'company_name'    => 'required|string|max:255',
            'company_tagline' => 'nullable|string|max:255',
            'primary_color'   => 'nullable|string|max:7',
            'company_logo'    => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'favicon'         => 'nullable|image|mimes:png,ico,jpg,jpeg,svg|max:1024',
            'remove_logo'     => 'nullable|boolean',
            'remove_favicon'  => 'nullable|boolean',
            // OpenWeatherMap API key — used by mobile daily log auto-weather
            // and the foreman dashboard live weather strip. 32 hex chars but
            // we just check max length so other providers' keys are accepted
            // if the user later swaps providers.
            'weather_api_key' => 'nullable|string|max:80',
        ]);

        Setting::set('company_name', $request->input('company_name'));
        Setting::set('company_tagline', $request->input('company_tagline', ''));
        Setting::set('primary_color', $request->input('primary_color', '#2563eb'));
        Setting::set('weather_api_key', trim((string) $request->input('weather_api_key', '')));

        if ($request->input('remove_logo')) {
            $oldLogo = Setting::get('company_logo');
            if ($oldLogo && file_exists(public_path($oldLogo))) {
                unlink(public_path($oldLogo));
            }
            Setting::set('company_logo', null);
        } elseif ($request->hasFile('company_logo')) {
            $oldLogo = Setting::get('company_logo');
            if ($oldLogo && file_exists(public_path($oldLogo))) {
                unlink(public_path($oldLogo));
            }
            $path = $request->file('company_logo')->store('settings', 'public_uploads');
            Setting::set('company_logo', '/uploads/' . $path);
        }

        if ($request->input('remove_favicon')) {
            $oldFav = Setting::get('favicon');
            if ($oldFav && file_exists(public_path($oldFav))) {
                unlink(public_path($oldFav));
            }
            Setting::set('favicon', null);
        } elseif ($request->hasFile('favicon')) {
            $oldFav = Setting::get('favicon');
            if ($oldFav && file_exists(public_path($oldFav))) {
                unlink(public_path($oldFav));
            }
            $path = $request->file('favicon')->store('settings', 'public_uploads');
            Setting::set('favicon', '/uploads/' . $path);
        }

        return response()->json(['message' => 'Website settings updated successfully']);
    }
}
