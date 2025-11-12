<?php

namespace App\Http\Resources;

use App\Actions\MoneyAction;
use Illuminate\Http\Request;

class TransactionResource extends BaseResource
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
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'type_label' => ucfirst($this->type),

            // Money values (raw integers)
            'amount' => $this->amount,
            'amount_per_unit' => $this->amount_per_unit,
            'price_per_unit' => $this->amount_per_unit, // Keep for backward compatibility
            'quantity' => $this->quantity,
            'vat_amount' => $this->vat_amount ?? 0,
            'total_amount' => $this->total_amount,

            // Formatted money values (strings)
            'formatted_amount' => $this->formatted_amount,
            'formatted_amount_per_unit' => $this->amount_per_unit ? MoneyAction::format($this->amount_per_unit, $this->house_of_zeros ?? 2, $this->currency, true) : null,
            'formatted_price_per_unit' => $this->amount_per_unit ? MoneyAction::format($this->amount_per_unit, $this->house_of_zeros ?? 2, $this->currency, true) : null, // Keep for backward compatibility
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

            // Direct IDs for forms (always included) - hashed
            'category_id' => $this->hashIdForModel($this->category_id, 'transactioncategory'),
            'supplier_id' => $this->hashIdForModel($this->supplier_id, 'supplier'),
            'crew_member_id' => $this->hashIdForModel($this->crew_member_id, 'user'),
            'vat_profile_id' => $this->hashIdForModel($this->vat_profile_id, 'vatprofile'),
            'amount_includes_vat' => $this->amount_includes_vat ?? false,

            // Relationships - use whenLoaded with closures for proper resource instantiation
            'vessel' => $this->whenLoaded('vessel', function () {
                return new VesselResource($this->vessel);
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->hashIdForModel($this->category->id, 'transactioncategory'),
                    'name' => $this->category->name,
                    'type' => $this->category->type,
                    'color' => $this->category->color,
                ];
            }),
            'supplier' => $this->whenLoaded('supplier', function () {
                return [
                    'id' => $this->hashIdForModel($this->supplier->id, 'supplier'),
                    'company_name' => $this->supplier->company_name,
                ];
            }),
            'crew_member' => $this->whenLoaded('crewMember', function () {
                return [
                    'id' => $this->hashIdForModel($this->crewMember->id, 'user'),
                    'name' => $this->crewMember->name,
                    'email' => $this->crewMember->email,
                ];
            }),
            'vat_profile' => $this->whenLoaded('vatProfile', function () {
                return [
                    'id' => $this->hashIdForModel($this->vatProfile->id, 'vatprofile'),
                    'name' => $this->vatProfile->name,
                    'percentage' => (float) $this->vatProfile->percentage,
                    'formatted_rate' => $this->vatProfile->formatted_rate,
                    'display_name' => $this->vatProfile->display_name,
                ];
            }),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->hashIdForModel($this->createdBy->id, 'user'),
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'attachments' => $this->whenLoaded('attachments', function () {
                return AttachmentResource::collection($this->attachments);
            }),
            'files' => $this->whenLoaded('files', function () {
                return $this->files->map(function ($file) {
                    return [
                        'id' => $this->hashIdForModel($file->id, 'transactionfile'),
                        'src' => $file->src,
                        'name' => $file->name,
                        'size' => $file->size,
                        'type' => $file->type,
                        'size_human' => $file->size_human,
                    ];
                });
            }),

            // Additional transaction metadata
            'transaction_month' => $this->transaction_month,
            'transaction_year' => $this->transaction_year,
            'recurring_transaction_id' => $this->hashIdForModel($this->recurring_transaction_id, 'transaction'),

            // Timestamps
            'created_at' => $this->created_at?->format('c'), // ISO 8601 format for sorting
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
            'created_at_datetime' => $this->created_at?->format('Y-m-d H:i:s'), // Base datetime format
            'updated_at' => $this->updated_at?->format('c'), // ISO 8601 format for sorting
            'updated_at_formatted' => $this->updated_at?->format('d/m/Y H:i'),
            'updated_at_datetime' => $this->updated_at?->format('Y-m-d H:i:s'), // Base datetime format
        ];
    }
}

