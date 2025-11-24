<?php

namespace App\Services;

use App\Actions\AuditLogAction;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselRoleAccess;
use App\Models\VesselSetting;
use App\Models\VesselUser;
use App\Models\VesselUserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VesselService
{
    /**
     * Create a new vessel with owner and initial administrator role.
     *
     * @param User $owner The user who will own the vessel (must be paid_system)
     * @param array $vesselData Vessel configuration data
     * @return Vessel The created vessel
     * @throws \Exception If user doesn't have tenant role or creation fails
     */
    public function createVessel(User $owner, array $vesselData): Vessel
    {
        // Validate that owner has tenant role (paid_system)
        if ($owner->user_type !== 'paid_system') {
            throw new \Exception('User must have tenant role (paid_system) to create vessels.');
        }

        return DB::transaction(function () use ($owner, $vesselData) {
            // Create the vessel
            $vessel = Vessel::create([
                'name'                => $vesselData['name'],
                'registration_number' => $vesselData['registration_number'],
                'vessel_type'         => $vesselData['vessel_type'],
                'capacity'            => $vesselData['capacity'] ?? null,
                'year_built'          => $vesselData['year_built'] ?? null,
                'status'              => $vesselData['status'],
                'notes'               => $vesselData['notes'] ?? null,
                'country_code'        => $vesselData['country_code'],
                'currency_code'       => $vesselData['currency_code'],
                'owner_id'            => $owner->id,
            ]);

            // Get or create the administrator role access
            $adminRoleAccess = VesselRoleAccess::where('name', 'administrator')->first();

            if (! $adminRoleAccess) {
                $adminRoleAccess = VesselRoleAccess::create([
                    'name'         => 'administrator',
                    'display_name' => 'Administrator',
                    'description'  => 'Full access to vessel including deletion and user management',
                    'permissions' => [
                        'view_vessel',
                        'edit_vessel_basic',
                        'edit_vessel_advanced',
                        'manage_crew',
                        'delete_vessel',
                        'manage_vessel_users',
                    ],
                    'is_active'    => true,
                ]);
            }

            // Create vessel user role with administrator access (mandatory)
            VesselUserRole::create([
                'vessel_id'             => $vessel->id,
                'user_id'               => $owner->id,
                'vessel_role_access_id' => $adminRoleAccess->id,
                'is_active'             => true,
            ]);

            // Also maintain the old vessel_users table for backward compatibility
            VesselUser::create([
                'vessel_id' => $vessel->id,
                'user_id'   => $owner->id,
                'role'      => 'owner',
                'is_active' => true,
            ]);

            // Create vessel setting with country, currency, and VAT profile
            if (isset($vesselData['vat_profile_id'])) {
                VesselSetting::create([
                    'vessel_id'     => $vessel->id,
                    'country_code'  => $vesselData['country_code'],
                    'currency_code' => $vesselData['currency_code'],
                    'vat_profile_id' => $vesselData['vat_profile_id'],
                ]);
            }

            // Log the create action
            AuditLogAction::logCreate(
                $vessel,
                'Vessel',
                $vessel->name,
                null // Vessels are not vessel-scoped (they're global entities)
            );

            Log::info('Vessel created successfully', [
                'vessel_id' => $vessel->id,
                'vessel_name' => $vessel->name,
                'owner_id' => $owner->id,
                'owner_email' => $owner->email,
            ]);

            return $vessel;
        });
    }
}

