<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function list(User $user, ?string $type = null, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->with(['sender' => fn ($q) => $q->with('media'), 'publication'])
            ->ofType($type)
            ->latest()
            ->paginate($perPage);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->update(['read_at' => now()]);
    }

    public function markAllAsRead(User $user, ?string $type = null): void
    {
        Notification::query()
            ->where('user_id', $user->id)
            ->unread()
            ->ofType($type)
            ->update(['read_at' => now()]);
    }

    public function unreadCount(User $user): array
    {
        $counts = Notification::query()
            ->where('user_id', $user->id)
            ->unread()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();

        return [
            'total' => array_sum($counts),
            'like' => $counts[Notification::TYPE_LIKE] ?? 0,
            'comment' => $counts[Notification::TYPE_COMMENT] ?? 0,
            'follow' => $counts[Notification::TYPE_FOLLOW] ?? 0,
            'follow_request' => $counts[Notification::TYPE_FOLLOW_REQUEST] ?? 0,
            'mention' => $counts[Notification::TYPE_MENTION] ?? 0,
            'review' => $counts[Notification::TYPE_REVIEW] ?? 0,
            'review_reply' => $counts[Notification::TYPE_REVIEW_REPLY] ?? 0,
            'comment_reply' => $counts[Notification::TYPE_COMMENT_REPLY] ?? 0,
            'message' => $counts[Notification::TYPE_MESSAGE] ?? 0,
        ];
    }

    public function notify(int $userId, int $senderId, string $type, ?int $publicationId = null): void
    {
        if ($userId === $senderId) {
            return;
        }

        // Don't notify if either user has blocked the other
        $user = User::find($userId);
        if ($user && ($user->hasBlocked($senderId) || $user->isBlockedBy($senderId))) {
            return;
        }

        Notification::firstOrCreate([
            'user_id' => $userId,
            'sender_id' => $senderId,
            'type' => $type,
            'publication_id' => $publicationId,
        ]);
    }
}
