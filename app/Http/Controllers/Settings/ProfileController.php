<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Traits\HasTranslations;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return Inertia::render('panel/Profile', [
            'user' => $user,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'hasHighVesselAccess' => $hasHighVesselAccess,
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
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);

            $user = $request->user();

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
}
