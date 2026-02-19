<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'parent_id' => $this->parent_id,
            'author' => new UserResource($this->whenLoaded('author')),
            'media' => PublicationMediaResource::collection($this->whenLoaded('media')),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
