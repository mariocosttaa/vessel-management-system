<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get country from relationship or auto-detect from IBAN
        $country = null;
        if ($this->relationLoaded('country') && $this->country) {
            $country = $this->country;
        } else {
            $country = $this->resource->getCountryOrDetectFromIban();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'iban' => $this->iban,
            'country_id' => $this->country_id,
            'country' => $country ? new CountryResource($country) : null,
            'initial_balance' => $this->initial_balance,
            'formatted_initial_balance' => $this->formatted_initial_balance,
            'current_balance' => $this->current_balance,
            'formatted_current_balance' => $this->formatted_current_balance,
            'status' => $this->status,
            'status_label' => ucfirst($this->status),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('d/m/Y'),
            'updated_at' => $this->updated_at?->format('d/m/Y'),
        ];
    }
}
