<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VesselResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'registration_number' => $this->registration_number,
            'vessel_type' => $this->vessel_type,
            'vessel_type_label' => $this->getVesselTypeLabel(),
            'capacity' => $this->capacity,
            'year_built' => $this->year_built,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'notes' => $this->notes,
            'country_code' => $this->country_code,
            'currency_code' => $this->currency_code,
            'country' => $this->whenLoaded('country', fn() => [
                'code' => $this->country->code,
                'name' => $this->country->name,
                'formatted_display' => $this->country->formatted_display,
            ]),
            'currency' => $this->whenLoaded('currency', fn() => [
                'code' => $this->currency->code,
                'name' => $this->currency->name,
                'symbol' => $this->currency->symbol,
                'formatted_display' => $this->currency->formatted_display,
            ]),
            'crew_members_count' => $this->whenLoaded('crewMembers', fn() => $this->crewMembers->count(), 0),
            'transactions_count' => $this->whenLoaded('transactions', fn() => $this->transactions->count(), 0),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get vessel type label
     */
    private function getVesselTypeLabel(): string
    {
        return match($this->vessel_type) {
            'cargo' => 'Cargo',
            'passenger' => 'Passenger',
            'fishing' => 'Fishing',
            'yacht' => 'Yacht',
            default => ucfirst($this->vessel_type),
        };
    }

    /**
     * Get status label
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'suspended' => 'Suspended',
            'maintenance' => 'Maintenance',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    private function getStatusColor(): string
    {
        return match($this->status) {
            'active' => 'green',
            'suspended' => 'orange',
            'maintenance' => 'yellow',
            default => 'gray',
        };
    }
}
