<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Traits\HasTranslations;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    use HasTranslations;
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        // Check if user has high-level access to any vessel
        $hasHighVesselAccess = false;
        $vessels = $user->vesselsThroughRoles()->get();

        foreach ($vessels as $vessel) {
            if ($user->hasHighVesselAccess($vessel->id)) {
                $hasHighVesselAccess = true;
                break;
            }
        }

        // Check if user has a real password (not just OAuth-generated)
        // OAuth users have a random password, but we can check if they have provider set
        // If they have provider and no password was manually set, they're OAuth-only
        $hasPassword = !empty($user->provider) ? false : true; // OAuth users don't need password for deletion

        return Inertia::render('panel/Profile', [
            'user' => $user,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'hasHighVesselAccess' => $hasHighVesselAccess,
            'oauth_connected' => [
                'google' => $user->provider === 'google' && !empty($user->provider_id),
                'microsoft' => $user->provider === 'microsoft' && !empty($user->provider_id),
            ],
            'requires_password_for_deletion' => $hasPassword,
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

            // Update notification preference if user has high vessel access
            // Check if user has high access to at least one vessel
            $hasHighVesselAccess = false;
            $vessels = $user->vesselsThroughRoles()->get();

            foreach ($vessels as $vessel) {
                if ($user->hasHighVesselAccess($vessel->id)) {
                    $hasHighVesselAccess = true;
                    break;
                }
            }

            // Update notification preference if user has high vessel access
            if ($hasHighVesselAccess && $request->has('vessel_admin_notification')) {
                // Convert to boolean: handle string 'true'/'false', boolean true/false, or integer 1/0
                $value = $request->input('vessel_admin_notification');
                $user->vessel_admin_notification = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            } elseif (!$hasHighVesselAccess) {
                // If user doesn't have high access, ensure it's false
                $user->vessel_admin_notification = false;
            }
            // If field is not provided and user has access, keep current value (don't change it)

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            return to_route('panel.profile.edit')
                ->with('success', $this->transFrom('notifications', 'Profile updated successfully.'))
                ->with('notification_delay', 3); // 3 seconds auto-dismiss
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Profile update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
                'request_data' => $request->all(),
                'has_vessel_admin_notification' => $request->has('vessel_admin_notification'),
                'vessel_admin_notification_value' => $request->input('vessel_admin_notification'),
            ]);

            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to update profile: :message', ['message' => $e->getMessage()]))
                ->with('notification_delay', 0); // Persistent error
        }
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();

            // Only require password if user doesn't have OAuth provider (regular account)
            // OAuth-only users don't have a password they know
            if (empty($user->provider)) {
                $request->validate([
                    'password' => ['required', 'current_password'],
                ]);
            }

            Auth::logout();

            $user->delete();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')
                ->with('success', $this->transFrom('notifications', 'Your account has been deleted successfully.'))
                ->with('notification_delay', 5); // 5 seconds auto-dismiss
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to delete account. Please try again.'))
                ->with('notification_delay', 0); // Persistent error
        }
    }

    /**
     * Update the user's language preference.
     */
    public function updateLanguage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language' => ['required', 'string', 'in:en,pt,es,fr'],
        ]);

        try {
            $user = $request->user();
            $user->language = $validated['language'];
            $user->save();

            // Set cookie for immediate use
            cookie()->queue('locale', $validated['language'], 60 * 24 * 365);

            // Redirect back to the current page to apply the new language
            return back()->with('success', $this->transFrom('notifications', 'Language updated successfully.'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Language update failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return back()->with('error', $this->transFrom('notifications', 'Failed to update language.'));
        }
    }

    /**
     * Disconnect OAuth account from user profile.
     */
    public function disconnectOAuth(Request $request, string $provider): RedirectResponse
    {
        try {
            $user = $request->user();

            // Validate provider
            if (!in_array($provider, ['google', 'microsoft'])) {
                return back()->with('error', 'Invalid OAuth provider.');
            }

            // Check if user has this provider connected
            if ($user->provider !== $provider) {
                return back()->with('error', ucfirst($provider) . ' account is not connected.');
            }

            // Note: Password is required in the database, so all users have a password
            // OAuth users get a random password, but they can still disconnect
            // They should set a proper password if they want to use password login

            // Disconnect the OAuth account
            $user->provider = null;
            $user->provider_id = null;
            $user->save();

            return redirect()->route('panel.profile.edit')
                ->with('success', ucfirst($provider) . ' account disconnected successfully.');
        } catch (\Exception $e) {
            Log::error('OAuth disconnect failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'provider' => $provider,
            ]);

            return back()->with('error', 'Failed to disconnect ' . $provider . ' account. Please try again.');
        }
    }
}
