<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VesselResource extends JsonResource
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
            'registration_number' => $this->registration_number,
            'vessel_type' => $this->vessel_type,
            'capacity' => $this->capacity,
            'year_built' => $this->year_built,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'notes' => $this->notes,
            'logo' => $this->logo,
            'logo_url' => $this->logo_url,
            'owner_id' => $this->owner_id,
            'country_code' => $this->country_code,
            'currency_code' => $this->currency_code,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
