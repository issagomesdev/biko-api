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
            'city' => $this->whenLoaded('city', fn () => $this->city ? [
                'id'    => $this->city->id,
                'name'  => $this->city->name,
                'state' => $this->city->state ? [
                    'id'   => $this->city->state->id,
                    'name' => $this->city->state->name,
                    'uf'   => $this->city->state->uf,
                ] : null,
            ] : null),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('tag')),
            'media' => PublicationMediaResource::collection($this->whenLoaded('media')),
            'mentions' => UserResource::collection($this->whenLoaded('mentions')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'is_liked'     => (bool) ($this->is_liked ?? false),
            'likes_count' => $this->whenCounted('likes'),
            'comments_count' => $this->whenCounted('comments'),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
