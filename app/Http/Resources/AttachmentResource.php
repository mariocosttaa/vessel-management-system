<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AttachmentResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hashId($this->id),
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'file_size_human' => $this->file_size_human,
            'description' => $this->description,
            'url' => $this->url,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->hashIdForModel($this->attachable_id, strtolower(class_basename($this->attachable_type))),
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn() => [
                'id' => $this->hashIdForModel($this->uploadedBy->id, 'user'),
                'name' => $this->uploadedBy->name,
                'email' => $this->uploadedBy->email,
            ]),
            'attachable' => $this->whenLoaded('attachable'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
