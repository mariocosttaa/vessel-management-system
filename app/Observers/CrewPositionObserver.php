<?php

namespace App\Observers;

use App\Models\CrewPosition;
use App\Models\User;
use App\Models\VesselUserRole;

class CrewPositionObserver
{
    /**
     * Handle the CrewPosition "updated" event.
     *
     * When a crew position's vessel_role_access_id changes, update all users
     * who have that position to have the corresponding vessel role.
     */
    public function updated(CrewPosition $crewPosition): void
    {
        // Check if the vessel_role_access_id changed
        if ($crewPosition->wasChanged('vessel_role_access_id')) {
            $newRoleId = $crewPosition->vessel_role_access_id;

            // Get all users with this position
            $users = User::where('position_id', $crewPosition->id)->get();

            foreach ($users as $user) {
                // Only update if user has a vessel assigned
                if (!$user->vessel_id) {
                    continue;
                }

                // Update or create VesselUserRole for each vessel the user has access to
                // For now, we'll update the role for the user's primary vessel
                if ($user->vessel_id) {
                    if ($newRoleId) {
                        // Update existing role or create new one
                        VesselUserRole::updateOrCreate(
                            [
                                'user_id'   => $user->id,
                                'vessel_id' => $user->vessel_id,
                            ],
                            [
                                'vessel_role_access_id' => $newRoleId,
                                'is_active'             => true,
                            ]
                        );
                    } else {
                        // If role is removed from position, set to default "normal" role
                        $normalRole = \App\Models\VesselRoleAccess::where('name', 'normal')->first();
                        if ($normalRole) {
                            VesselUserRole::updateOrCreate(
                                [
                                    'user_id'   => $user->id,
                                    'vessel_id' => $user->vessel_id,
                                ],
                                [
                                    'vessel_role_access_id' => $normalRole->id,
                                    'is_active'             => true,
                                ]
                            );
                        }
                    }
                }

                // Also update roles for all vessels the user has access to through vessel_user_roles
                $userVesselRoles = VesselUserRole::where('user_id', $user->id)->get();
                foreach ($userVesselRoles as $vur) {
                    if ($newRoleId) {
                        $vur->update([
                            'vessel_role_access_id' => $newRoleId,
                            'is_active'             => true,
                        ]);
                    } else {
                        // Set to normal if role removed
                        $normalRole = \App\Models\VesselRoleAccess::where('name', 'normal')->first();
                        if ($normalRole) {
                            $vur->update([
                                'vessel_role_access_id' => $normalRole->id,
                                'is_active'             => true,
                            ]);
                        }
                    }
                }
            }
        }
    }
}

