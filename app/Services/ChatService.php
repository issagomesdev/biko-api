<?php

namespace App\Services;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserOnlineStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ChatService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function listConversations(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $blockedIds = $user->blockedUsers()->pluck('users.id')->all();
        $blockedByIds = $user->blockedByUsers()->pluck('users.id')->all();
        $excludeIds = array_merge($blockedIds, $blockedByIds);

        return Conversation::query()
            ->forUser($user->id)
            ->when(! empty($excludeIds), function ($query) use ($excludeIds, $user) {
                $query->where(function ($q) use ($excludeIds, $user) {
                    $q->where(function ($sub) use ($excludeIds, $user) {
                        $sub->where('user_one_id', $user->id)
                            ->whereNotIn('user_two_id', $excludeIds);
                    })->orWhere(function ($sub) use ($excludeIds, $user) {
                        $sub->where('user_two_id', $user->id)
                            ->whereNotIn('user_one_id', $excludeIds);
                    });
                });
            })
            ->with([
                'userOne.media',
                'userTwo.media',
                'latestMessage.sender',
            ])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)->unread();
            }])
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->latest()
                    ->limit(1)
            )
            ->paginate($perPage);
    }

    public function findOrCreateConversation(int $userId, int $otherUserId): Conversation
    {
        [$min, $max] = [min($userId, $otherUserId), max($userId, $otherUserId)];

        return Conversation::firstOrCreate(
            ['user_one_id' => $min, 'user_two_id' => $max]
        );
    }

    public function getMessages(Conversation $conversation, int $perPage = 50): LengthAwarePaginator
    {
        return $conversation->messages()
            ->with(['sender.media', 'replyTo.sender'])
            ->latest()
            ->paginate($perPage);
    }

    public function sendMessage(Conversation $conversation, int $senderId, string $body, ?int $replyToId = null): Message
    {
        $recipientId = $conversation->getOtherUserId($senderId);
        $recipient = User::find($recipientId);
        if ($recipient && ($recipient->hasBlocked($senderId) || $recipient->isBlockedBy($senderId))) {
            abort(403, 'Blocked.');
        }

        $message = $conversation->messages()->create([
            'sender_id' => $senderId,
            'body' => $body,
            'reply_to_id' => $replyToId,
        ]);

        $message->load(['sender.media', 'replyTo.sender']);

        broadcast(new MessageSent($message))->toOthers();

        $recipientId = $conversation->getOtherUserId($senderId);
        $this->notificationService->notify(
            $recipientId,
            $senderId,
            Notification::TYPE_MESSAGE,
        );

        return $message;
    }

    public function markAsRead(Conversation $conversation, int $userId): void
    {
        $messageIds = $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->unread()
            ->pluck('id')
            ->all();

        if (empty($messageIds)) {
            return;
        }

        Message::whereIn('id', $messageIds)->update(['read_at' => now()]);

        broadcast(new MessageRead($conversation->id, $messageIds))->toOthers();
    }

    public function deleteMessage(Message $message): void
    {
        $message->delete();
    }

    public function updateLastSeen(User $user): void
    {
        $cacheKey = "user_last_seen_{$user->id}";

        if (Cache::has($cacheKey)) {
            return;
        }

        $wasOffline = ! $user->isOnline();

        $user->update(['last_seen_at' => now()]);

        Cache::put($cacheKey, true, 60);

        if ($wasOffline) {
            broadcast(new UserOnlineStatus($user));
        }
    }
}
