<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CurrencyResource extends BaseResource
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
            'code' => $this->code,
            'symbol' => $this->symbol,
            'symbol_2' => $this->symbol_2,
            'decimal_separator' => $this->decimal_separator,
            'formatted_display' => $this->formatted_display,
            'display_symbol' => $this->display_symbol,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
