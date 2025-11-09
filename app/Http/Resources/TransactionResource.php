<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'type_label' => ucfirst($this->type),

            // Money values (raw integers)
            'amount' => $this->amount,
            'vat_amount' => $this->vat_amount ?? 0,
            'total_amount' => $this->total_amount,

            // Formatted money values (strings)
            'formatted_amount' => $this->formatted_amount,
            'formatted_vat_amount' => $this->formatted_vat_amount,
            'formatted_total_amount' => $this->formatted_total_amount,

            // Currency information
            'currency' => $this->currency,
            'house_of_zeros' => $this->house_of_zeros,

            // Dates
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'formatted_transaction_date' => $this->transaction_date?->format('d/m/Y'),

            // Descriptions
            'description' => $this->description,
            'notes' => $this->notes,
            'reference' => $this->reference,

            // Status
            'status' => $this->status,
            'status_label' => ucfirst($this->status),

            // Flags
            'is_recurring' => $this->is_recurring,

            // Direct IDs for forms (always included)
            'category_id' => $this->category_id,
            'supplier_id' => $this->supplier_id,
            'crew_member_id' => $this->crew_member_id,
            'vat_profile_id' => $this->vat_profile_id,
            'amount_includes_vat' => $this->amount_includes_vat ?? false,

            // Relationships - use whenLoaded with closures for proper resource instantiation
            'vessel' => $this->whenLoaded('vessel', function () {
                return new VesselResource($this->vessel);
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'type' => $this->category->type,
                    'color' => $this->category->color,
                ];
            }),
            'supplier' => $this->whenLoaded('supplier', function () {
                return [
                    'id' => $this->supplier->id,
                    'company_name' => $this->supplier->company_name,
                ];
            }),
            'crew_member' => $this->whenLoaded('crewMember', function () {
                return [
                    'id' => $this->crewMember->id,
                    'name' => $this->crewMember->name,
                    'email' => $this->crewMember->email,
                ];
            }),
            'vat_profile' => $this->whenLoaded('vatProfile', function () {
                return [
                    'id' => $this->vatProfile->id,
                    'name' => $this->vatProfile->name,
                    'percentage' => (float) $this->vatProfile->percentage,
                    'formatted_rate' => $this->vatProfile->formatted_rate,
                    'display_name' => $this->vatProfile->display_name,
                ];
            }),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'attachments' => $this->whenLoaded('attachments', function () {
                return AttachmentResource::collection($this->attachments);
            }),

            // Additional transaction metadata
            'transaction_month' => $this->transaction_month,
            'transaction_year' => $this->transaction_year,
            'recurring_transaction_id' => $this->recurring_transaction_id,

            // Timestamps
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}

