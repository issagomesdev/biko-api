<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_default' => $this->is_default,
            'publications_count' => $this->whenCounted('publications'),
            'publications' => PublicationResource::collection($this->whenLoaded('publications')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
