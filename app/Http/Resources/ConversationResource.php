<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authUserId = $request->user()?->id;

        $otherUser = $this->user_one_id === $authUserId
            ? $this->whenLoaded('userTwo')
            : $this->whenLoaded('userOne');

        return [
            'id' => $this->id,
            'other_user' => new UserResource($otherUser),
            'latest_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'unread_count' => $this->unread_count ?? 0,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
