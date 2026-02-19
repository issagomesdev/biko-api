<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'type' => $this->type,
            'author' => new UserResource($this->whenLoaded('author')),
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ]),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('tag')),
            'media' => PublicationMediaResource::collection($this->whenLoaded('media')),
            'mentions' => UserResource::collection($this->whenLoaded('mentions')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'likes_count' => $this->whenCounted('likes'),
            'comments_count' => $this->whenCounted('comments'),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
