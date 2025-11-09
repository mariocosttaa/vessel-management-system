<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\VatProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VatConfigurationController extends Controller
{
    /**
     * Show the VAT configuration settings page.
     */
    public function edit(Request $request)
    {
        // Get all active VAT profiles
        $vatProfiles = VatProfile::active()->with('country')->orderBy('name')->get();

        // Get current default VAT profile ID from settings
        $defaultVatProfileId = SystemSetting::getValue('default_vat_profile_id');
        $defaultVatProfile = $defaultVatProfileId ? VatProfile::find($defaultVatProfileId) : null;

        return Inertia::render('settings/VatConfiguration', [
            'vatProfiles' => $vatProfiles->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'percentage' => (float) $profile->percentage,
                    'code' => $profile->code,
                    'country' => $profile->country ? [
                        'id' => $profile->country->id,
                        'name' => $profile->country->name,
                        'code' => $profile->country->code,
                    ] : null,
                    'description' => $profile->description,
                    'is_default' => $profile->is_default,
                ];
            }),
            'defaultVatProfileId' => $defaultVatProfileId,
            'defaultVatProfile' => $defaultVatProfile ? [
                'id' => $defaultVatProfile->id,
                'name' => $defaultVatProfile->name,
                'percentage' => (float) $defaultVatProfile->percentage,
                'code' => $defaultVatProfile->code,
                'country' => $defaultVatProfile->country ? [
                    'id' => $defaultVatProfile->country->id,
                    'name' => $defaultVatProfile->country->name,
                    'code' => $defaultVatProfile->country->code,
                ] : null,
            ] : null,
        ]);
    }

    /**
     * Update the VAT configuration settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'default_vat_profile_id' => ['nullable', 'integer', 'exists:vat_profiles,id'],
        ]);

        // Update the default VAT profile in settings
        if ($request->default_vat_profile_id) {
            SystemSetting::setValue(
                'default_vat_profile_id',
                $request->default_vat_profile_id,
                'integer',
                'Default VAT profile ID for transactions'
            );
        } else {
            // Remove the setting if no profile is selected
            SystemSetting::where('key', 'default_vat_profile_id')->delete();
        }

        return back()->with('success', 'VAT configuration updated successfully.');
    }
}
