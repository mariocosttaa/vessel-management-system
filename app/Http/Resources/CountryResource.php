<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CountryResource extends BaseResource
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
            'capital_city' => $this->capital_city,
            'code' => $this->code,
            'calling_code' => $this->calling_code,
            'formatted_display' => $this->formatted_display,
            'with_calling_code' => $this->with_calling_code,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
