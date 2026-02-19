<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stars' => $this->stars,
            'comment' => $this->comment,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
                'thumb_url' => str_starts_with($media->mime_type, 'image/')
                    ? ($media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null)
                    : null,
                'type' => str_starts_with($media->mime_type, 'video/') ? 'video' : 'image',
                'created_at' => $media->created_at->toISOString(),
            ])),
            'replies' => ReviewResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
