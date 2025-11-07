<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrewMemberResource extends JsonResource
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
            'vessel' => new VesselResource($this->whenLoaded('vessel')),
            'position_id' => $this->position_id,
            'position_name' => $this->whenLoaded('position', fn() => $this->position->name),
            'name' => $this->name,
            'document_number' => $this->document_number ?? null,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'formatted_date_of_birth' => $this->date_of_birth?->format('d/m/Y'),
            'hire_date' => $this->hire_date?->format('Y-m-d'),
            'formatted_hire_date' => $this->hire_date?->format('d/m/Y'),
            'salary_compensation' => $this->whenLoaded('activeSalaryCompensation', function () {
                $compensation = $this->activeSalaryCompensation->first();
                if (!$compensation) return null;

                return [
                    'compensation_type' => $compensation->compensation_type,
                    'fixed_amount' => $compensation->fixed_amount,
                    'formatted_fixed_amount' => $compensation->formatted_fixed_amount,
                    'percentage' => $compensation->percentage,
                    'formatted_percentage' => $compensation->formatted_percentage,
                    'currency' => $compensation->currency,
                    'payment_frequency' => $compensation->payment_frequency,
                    'payment_frequency_label' => $this->getPaymentFrequencyLabel($compensation->payment_frequency),
                ];
            }),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'login_permitted' => $this->login_permitted,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Get the payment frequency label.
     */
    private function getPaymentFrequencyLabel(?string $frequency = null): string
    {
        $frequency = $frequency ?? $this->payment_frequency;
        return match($frequency) {
            'weekly' => 'Weekly',
            'bi_weekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'annually' => 'Annually',
            default => ucfirst($frequency),
        };
    }

    /**
     * Get the status label.
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'on_leave' => 'On Leave',
            default => ucfirst($this->status),
        };
    }
}
