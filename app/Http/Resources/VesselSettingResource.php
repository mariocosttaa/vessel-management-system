<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VesselSettingResource extends JsonResource
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
            'vessel_id' => $this->vessel_id,
            'country_code' => $this->country_code,
            'currency_code' => $this->currency_code,
            'vat_profile_id' => $this->vat_profile_id,
            'country' => $this->whenLoaded('country', function () {
                return [
                    'code' => $this->country->code,
                    'name' => $this->country->name,
                    'formatted_display' => $this->country->formatted_display,
                ];
            }),
            'currency' => $this->whenLoaded('currency', function () {
                return [
                    'code' => $this->currency->code,
                    'name' => $this->currency->name,
                    'symbol' => $this->currency->symbol,
                ];
            }),
            'vat_profile' => $this->whenLoaded('vatProfile', function () {
                return [
                    'id' => $this->vatProfile->id,
                    'name' => $this->vatProfile->name,
                    'percentage' => $this->vatProfile->percentage,
                    'formatted_rate' => $this->vatProfile->formatted_rate,
                    'display_name' => $this->vatProfile->display_name,
                    'country' => $this->vatProfile->relationLoaded('country') ? [
                        'id' => $this->vatProfile->country->id,
                        'code' => $this->vatProfile->country->code,
                        'name' => $this->vatProfile->country->name,
                    ] : null,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
