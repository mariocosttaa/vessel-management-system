<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('panel/Profile', [
            'user' => $request->user(),
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $user->name = $request->name;
            $user->email = $request->email;

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            return to_route('panel.profile.edit')
                ->with('success', 'Profile updated successfully.')
                ->with('notification_delay', 3); // 3 seconds auto-dismiss
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update profile. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);

            $user = $request->user();

            Auth::logout();

            $user->delete();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')
                ->with('success', 'Your account has been deleted successfully.')
                ->with('notification_delay', 5); // 5 seconds auto-dismiss
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete account. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }
}
