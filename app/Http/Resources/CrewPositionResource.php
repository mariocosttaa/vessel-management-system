<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrewPositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'vessel_id' => $this->vessel_id,
            'vessel_role_access_id' => $this->vessel_role_access_id,
            'is_global' => $this->vessel_id === null,
            'scope_label' => $this->vessel_id === null ? 'Default' : 'Created',

            // Relationships
            'vessel' => new VesselResource($this->whenLoaded('vessel')),
            'crew_members' => CrewMemberResource::collection($this->whenLoaded('crewMembers')),
            'crew_members_count' => $this->whenCounted('crewMembers', fn() => $this->crew_members_count) ?:
                ($this->relationLoaded('crewMembers') ? $this->crewMembers->count() : 0),
            'vessel_role_access' => $this->whenLoaded('vesselRoleAccess', function () {
                return [
                    'id' => $this->vesselRoleAccess->id,
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

