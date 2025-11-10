<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVesselGeneralRequest;
use App\Http\Requests\UpdateVesselLocationRequest;
use App\Http\Resources\VesselResource;
use App\Http\Resources\VesselSettingResource;
use App\Models\Country;
use App\Models\Currency;
use App\Models\VatProfile;
use App\Models\Vessel;
use App\Models\VesselSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VesselSettingController extends Controller
{
    /**
     * Show the form for editing vessel settings.
     */
    public function edit(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from route parameter
        $vessel = $request->route('vessel');
        $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;

        // Check if user has permission to manage vessel settings
        if (!$user || !$user->hasAnyRoleForVessel($vesselId, ['Administrator', 'Supervisor'])) {
            abort(403, 'You do not have permission to manage vessel settings.');
        }

        // Get the vessel
        $vessel = Vessel::findOrFail($vesselId);

        // Get or create settings for the vessel
        $setting = VesselSetting::getForVessel($vesselId);

        // Load relationships
        $setting->load(['country', 'currency', 'vatProfile']);

        // Get options for dropdowns
        $countries = Country::orderBy('name')->get(['code', 'name']);
        // Get all currencies, not just active ones
        $currencies = Currency::orderBy('name')->get(['code', 'name', 'symbol']);
        $vatProfiles = VatProfile::active()->with('country')->orderBy('name')->get();

        // Vessel types for dropdown
        $vesselTypes = ['cargo', 'passenger', 'fishing', 'yacht', 'tanker', 'container', 'cruise', 'other'];
        $statuses = ['active', 'suspended', 'maintenance'];

        return Inertia::render('settings/VesselSettings', [
            'vessel' => new VesselResource($vessel),
            'setting' => new VesselSettingResource($setting),
            'countries' => $countries,
            'currencies' => $currencies,
            'vatProfiles' => $vatProfiles,
            'vesselTypes' => $vesselTypes,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Update vessel general information.
     */
    public function updateGeneral(UpdateVesselGeneralRequest $request)
    {
        try {
            // Get vessel_id from route parameter
            $vessel = $request->route('vessel');
            $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;

            // Get the vessel and update
            $vessel = Vessel::findOrFail($vesselId);

            // Log received data for debugging
            Log::info('Vessel settings update request', [
                'method' => $request->method(),
                'vessel_id' => $vesselId,
                'has_logo_file' => $request->hasFile('logo'),
                'remove_logo' => $request->boolean('remove_logo'),
                'all_input_keys' => array_keys($request->all()),
                'received_fields' => [
                    'name' => $request->has('name') ? 'present' : 'missing',
                    'registration_number' => $request->has('registration_number') ? 'present' : 'missing',
                    'vessel_type' => $request->has('vessel_type') ? 'present' : 'missing',
                    'status' => $request->has('status') ? 'present' : 'missing',
                    'capacity' => $request->has('capacity') ? 'present' : 'missing',
                    'year_built' => $request->has('year_built') ? 'present' : 'missing',
                    'notes' => $request->has('notes') ? 'present' : 'missing',
                ],
                'field_values' => [
                    'name' => $request->input('name'),
                    'registration_number' => $request->input('registration_number'),
                    'vessel_type' => $request->input('vessel_type'),
                    'status' => $request->input('status'),
                    'capacity' => $request->input('capacity'),
                    'year_built' => $request->input('year_built'),
                    'notes' => $request->input('notes'),
                ],
                'content_type' => $request->header('Content-Type'),
                'is_json' => $request->isJson(),
            ]);

            $updateData = [
                'name' => $request->input('name'),
                'registration_number' => $request->input('registration_number'),
                'vessel_type' => $request->input('vessel_type'),
                'capacity' => $request->input('capacity'),
                'year_built' => $request->input('year_built'),
                'status' => $request->input('status'),
                'notes' => $request->input('notes'),
            ];

            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($vessel->logo && Storage::disk('public')->exists($vessel->logo)) {
                    Storage::disk('public')->delete($vessel->logo);
                }

                // Store new logo
                $logoPath = $request->file('logo')->store('vessels/logos', 'public');
                $updateData['logo'] = $logoPath;
            } elseif ($request->boolean('remove_logo')) {
                // Delete logo if remove_logo is true
                if ($vessel->logo && Storage::disk('public')->exists($vessel->logo)) {
                    Storage::disk('public')->delete($vessel->logo);
                }
                $updateData['logo'] = null;
            }

            $vessel->update($updateData);

            // Reload vessel to get updated logo URL
            $vessel->refresh();

            return back()
                ->with('success', 'Vessel information has been updated successfully.')
                ->with('notification_delay', 3);
        } catch (\Exception $e) {
            Log::error('Vessel general update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->validated(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update vessel information. Please try again.')
                ->with('notification_delay', 0);
        }
    }

    /**
     * Update vessel location and currency settings.
     */
    public function updateLocation(UpdateVesselLocationRequest $request)
    {
        try {
            // Get vessel_id from route parameter
            $vessel = $request->route('vessel');
            $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;

            // Get or create settings for the vessel, then update
            $setting = VesselSetting::getForVessel($vesselId);
            $setting->update([
                'country_code' => $request->country_code,
                'currency_code' => $request->currency_code,
                'vat_profile_id' => $request->vat_profile_id,
            ]);

            // Also update vessel's country and currency if they exist on the vessel table
            $vessel = Vessel::findOrFail($vesselId);
            $vessel->update([
                'country_code' => $request->country_code,
                'currency_code' => $request->currency_code,
            ]);

            // Reload with relationships for the response
            $setting->refresh();
            $setting->load(['country', 'currency', 'vatProfile']);

            return redirect()
                ->route('panel.settings.edit', ['vessel' => $vesselId])
                ->with('success', 'Vessel location settings have been updated successfully.')
                ->with('notification_delay', 3);
        } catch (\Exception $e) {
            Log::error('Vessel location settings update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->validated(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update vessel location settings. Please try again.')
                ->with('notification_delay', 0);
        }
    }
}
