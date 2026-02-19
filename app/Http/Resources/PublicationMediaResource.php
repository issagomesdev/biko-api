<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicationMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'url' => $this->getUrl(),
            'thumb_url' => $this->hasGeneratedConversion('thumb') ? $this->getUrl('thumb') : null,
            'type' => str_starts_with($this->mime_type, 'image/') ? 'image' : 'video',
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
