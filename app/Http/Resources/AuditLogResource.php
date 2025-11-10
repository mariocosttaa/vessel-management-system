<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name ?? 'System',
            'user_email' => $this->user?->email ?? null,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'model_name' => $this->getModelDisplayName(), // Display name for "Page"
            'page_name' => $this->getModelDisplayName(), // Alias for frontend clarity
            'action' => $this->action,
            'message' => $this->message,
            'vessel_id' => $this->vessel_id,
            'vessel_name' => $this->vessel?->name,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }

    /**
     * Get the display name for the model type.
     */
    protected function getModelDisplayName(): string
    {
        $className = class_basename($this->model_type);

        // Convert CamelCase to words (e.g., "CrewPosition" -> "Crew Position")
        return preg_replace('/(?<!^)([A-Z])/', ' $1', $className);
    }
}

