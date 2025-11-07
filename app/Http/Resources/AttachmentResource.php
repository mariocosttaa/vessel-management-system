<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'file_size_human' => $this->file_size_human,
            'description' => $this->description,
            'url' => $this->url,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn() => [
                'id' => $this->uploadedBy->id,
                'name' => $this->uploadedBy->name,
                'email' => $this->uploadedBy->email,
            ]),
            'attachable' => $this->whenLoaded('attachable'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
