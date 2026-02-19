<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        // Soft-deleted user: show minimal data
        if ($this->trashed()) {
            return [
                'id' => $this->id,
                'name' => 'Usuário deletado',
                'is_deleted' => true,
            ];
        }

        $authUser = $request->user();

        $isOwner = $authUser && $authUser->id === $this->id;
        $isPublic = ! $this->is_private;

        // Check if blocked by this user (auth user was blocked)
        $isBlockedByThisUser = false;
        $isBlocked = false;

        if ($authUser && ! $isOwner) {
            $isBlockedByThisUser = $this->isBlockedBy($authUser->id);
            $isBlocked = $this->hasBlocked($authUser->id);
        }

        // If auth user was blocked by this user, return minimal data
        if ($isBlockedByThisUser) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'is_blocked' => $isBlocked,
            ];
        }

        $isFollowing = false;
        $isPending = false;

        if ($authUser && ! $isOwner) {
            $isFollowing = $this->relationLoaded('followers')
                ? $this->followers->contains('id', $authUser->id)
                : $this->isFollowedBy($authUser->id);

            if (! $isFollowing && $this->is_private) {
                $isPending = $this->hasPendingFollowFrom($authUser->id);
            }
        }

        $canSeeFullProfile = $isOwner || $isPublic || $isFollowing;

        $avatar = $this->whenLoaded('media', function () {
            $media = $this->getFirstMedia('avatar');

            return $media ? [
                'url' => $media->getUrl(),
                'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null,
            ] : null;
        });

        $cover = $this->whenLoaded('media', function () {
            $media = $this->getFirstMedia('cover');

            return $media ? [
                'url' => $media->getUrl(),
                'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null,
            ] : null;
        });

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'description' => $this->description,
            'is_private' => $this->is_private,
            'avatar' => $avatar,
            'cover' => $cover,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'followers_count' => $this->whenCounted('followers'),
            'following_count' => $this->whenCounted('following'),
            'average_rating' => $this->averageRating(),
            'reviews_count' => $this->whenCounted('reviews'),
            'is_online' => $this->isOnline(),
            'is_following' => $isFollowing,
            'is_pending' => $isPending,
            'is_blocked' => $isBlocked,
        ];

        if ($canSeeFullProfile) {
            $data['email'] = $this->email;
            $data['phone'] = $this->phone;
            $data['city'] = $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ]);
            $data['last_seen_at'] = $this->last_seen_at?->toISOString();
            $data['created_at'] = $this->created_at->toISOString();
        }

        return $data;
    }
}
