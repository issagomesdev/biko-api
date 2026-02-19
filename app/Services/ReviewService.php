<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReviewService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function listForUser(User $user, int $perPage = 20, ?int $authUserId = null): LengthAwarePaginator
    {
        $query = $user->reviews()
            ->root()
            ->with(['reviewer' => fn ($q) => $q->with('media'), 'media', 'replies.reviewer' => fn ($q) => $q->with('media'), 'replies.media'])
            ->latest();

        if ($authUserId) {
            $blockedIds = User::find($authUserId)?->blockedUsers()->pluck('users.id')->all() ?? [];
            $blockedByIds = User::find($authUserId)?->blockedByUsers()->pluck('users.id')->all() ?? [];
            $excludeIds = array_merge($blockedIds, $blockedByIds);
            if (! empty($excludeIds)) {
                $query->whereNotIn('reviewer_id', $excludeIds);
            }
        }

        return $query->paginate($perPage);
    }

    public function create(User $user, int $reviewerId, int $stars, string $comment, array $mediaFiles = []): Review
    {
        $review = Review::create([
            'user_id' => $user->id,
            'reviewer_id' => $reviewerId,
            'stars' => $stars,
            'comment' => $comment,
        ]);

        foreach ($mediaFiles as $file) {
            $review->addMedia($file)->toMediaCollection('media');
        }

        $this->notificationService->notify(
            $user->id,
            $reviewerId,
            Notification::TYPE_REVIEW,
        );

        return $review->load(['reviewer' => fn ($q) => $q->with('media'), 'media']);
    }

    public function reply(Review $review, int $userId, string $comment, array $mediaFiles = []): Review
    {
        $rootReview = $review->parent_id ? $review->parent : $review;

        $reply = Review::create([
            'user_id' => $rootReview->user_id,
            'reviewer_id' => $userId,
            'comment' => $comment,
            'parent_id' => $rootReview->id,
        ]);

        foreach ($mediaFiles as $file) {
            $reply->addMedia($file)->toMediaCollection('media');
        }

        // Notify the root review author if replier is different
        $this->notificationService->notify(
            $rootReview->reviewer_id,
            $userId,
            Notification::TYPE_REVIEW_REPLY,
        );

        // Also notify the reviewed user if different from replier and root reviewer
        if ($rootReview->user_id !== $rootReview->reviewer_id) {
            $this->notificationService->notify(
                $rootReview->user_id,
                $userId,
                Notification::TYPE_REVIEW_REPLY,
            );
        }

        return $reply->load(['reviewer' => fn ($q) => $q->with('media'), 'media']);
    }

    public function update(Review $review, array $data, array $mediaFiles = [], array $removeMediaIds = []): Review
    {
        $review->update($data);

        if (! empty($removeMediaIds)) {
            $review->media()
                ->whereIn('id', $removeMediaIds)
                ->each(fn ($media) => $media->delete());
        }

        foreach ($mediaFiles as $file) {
            $review->addMedia($file)->toMediaCollection('media');
        }

        return $review->load(['reviewer' => fn ($q) => $q->with('media'), 'media']);
    }

    public function delete(Review $review): void
    {
        $review->delete();
    }
}
