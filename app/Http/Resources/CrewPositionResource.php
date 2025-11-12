<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CrewPositionResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hashId($this->id),
            'name' => $this->name,
            'description' => $this->description,
            'vessel_id' => $this->hashIdForModel($this->vessel_id, 'vessel'),
            'vessel_role_access_id' => $this->hashIdForModel($this->vessel_role_access_id, 'vesselroleaccess'),
            'is_global' => $this->vessel_id === null,
            'scope_label' => $this->vessel_id === null ? 'Default' : 'Created',

            // Relationships
            'vessel' => new VesselResource($this->whenLoaded('vessel')),
            'crew_members' => CrewMemberResource::collection($this->whenLoaded('crewMembers')),
            'crew_members_count' => $this->whenCounted('crewMembers', fn() => $this->crew_members_count) ?:
                ($this->relationLoaded('crewMembers') ? $this->crewMembers->count() : 0),
            'vessel_role_access' => $this->whenLoaded('vesselRoleAccess', function () {
                return [
                    'id' => $this->hashIdForModel($this->vesselRoleAccess->id, 'vesselroleaccess'),
                    'name' => $this->vesselRoleAccess->name,
                    'display_name' => $this->vesselRoleAccess->display_name,
                    'description' => $this->vesselRoleAccess->description,
                ];
            }),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

