<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Publication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PublicationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}
    public function assertCanView(Publication $publication, ?User $authUser): void
    {
        $author = $publication->relationLoaded('author')
            ? $publication->author
            : $publication->load('author')->author;

        if ($authUser && $authUser->id !== $author->id) {
            if ($author->hasBlocked($authUser->id) || $author->isBlockedBy($authUser->id)) {
                abort(404, 'Publicação não encontrada.');
            }
        }

        if ($author->is_private) {
            $isOwner = $authUser && $authUser->id === $author->id;
            $isFollower = $authUser && $author->isFollowedBy($authUser->id);

            if (! $isOwner && ! $isFollower) {
                abort(404, 'Publicação não encontrada.');
            }
        }
    }

    public function list(array $filters = [], ?int $authUserId = null): LengthAwarePaginator
    {
        $query = Publication::query()
            ->select('publications.*')
            ->with(['author' => fn ($q) => $q->withCount(['followers', 'following']), 'categories', 'tags', 'city', 'media', 'mentions'])
            ->withCount('comments', 'likes')
            ->search($filters['search'] ?? null)
            ->ofType(isset($filters['type']) ? (int) $filters['type'] : null)
            ->inCategories($filters['categories'] ?? null)
            ->inCity(isset($filters['city_id']) ? (int) $filters['city_id'] : null)
            ->withTags($filters['tags'] ?? null);

        $this->applyPrivacyFilter($query, $authUserId);
        $this->applyDateFilter($query, $filters);
        $this->applySorting($query, $filters['orderBy'] ?? 'desc');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function findWithRelations(Publication $publication): Publication
    {
        return $publication->load([
            'author' => fn ($q) => $q->withCount(['followers', 'following']),
            'categories',
            'comments' => fn ($q) => $q->whereNull('parent_id')->with(['author', 'media', 'replies.author', 'replies.media']),
            'likes',
            'media',
            'mentions',
        ]);
    }

    public function create(array $data, ?array $categoryIds = null, ?array $tags = null, array $mediaFiles = [], ?array $mentionIds = null): Publication
    {
        $publication = Publication::create($data);

        if (! empty($categoryIds)) {
            $publication->categories()->attach($categoryIds);
        }

        $this->syncTags($publication, $tags);
        $this->syncMentions($publication, $mentionIds);
        $this->addMedia($publication, $mediaFiles);

        return $publication->load(['author' => fn ($q) => $q->withCount(['followers', 'following']), 'categories', 'tags', 'media', 'mentions']);
    }

    public function update(Publication $publication, array $data, ?array $categoryIds = null, ?array $tags = null, array $mediaFiles = [], array $removeMediaIds = [], ?array $mentionIds = null): Publication
    {
        $publication->update($data);

        if (! is_null($categoryIds)) {
            $publication->categories()->sync($categoryIds);
        }

        if (! is_null($tags)) {
            $this->syncTags($publication, $tags);
        }

        $this->syncMentions($publication, $mentionIds);
        $this->removeMedia($publication, $removeMediaIds);
        $this->addMedia($publication, $mediaFiles);

        return $publication->load(['author' => fn ($q) => $q->withCount(['followers', 'following']), 'categories', 'tags', 'media', 'mentions']);
    }

    public function delete(Publication $publication): void
    {
        $publication->delete();
    }

    public function toggleLike(Publication $publication, int $userId): bool
    {
        $author = $publication->author ?? $publication->load('author')->author;
        if ($author->hasBlocked($userId) || $author->isBlockedBy($userId)) {
            abort(403, 'Blocked.');
        }

        $existing = $publication->likes()->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        $publication->likes()->create(['user_id' => $userId]);

        $this->notificationService->notify(
            $publication->user_id,
            $userId,
            Notification::TYPE_LIKE,
            $publication->id,
        );

        return true;
    }

    public function addComment(Publication $publication, int $userId, string $comment, array $mediaFiles = [], ?int $parentId = null): Publication
    {
        $author = $publication->author ?? $publication->load('author')->author;
        if ($author->hasBlocked($userId) || $author->isBlockedBy($userId)) {
            abort(403, 'Blocked.');
        }

        $newComment = $publication->comments()->create([
            'user_id' => $userId,
            'comment' => $comment,
            'parent_id' => $parentId,
        ]);

        foreach ($mediaFiles as $file) {
            $newComment->addMedia($file)->toMediaCollection('media');
        }

        if ($parentId) {
            $parentComment = Comment::find($parentId);
            if ($parentComment && $parentComment->user_id !== $userId) {
                $this->notificationService->notify(
                    $parentComment->user_id,
                    $userId,
                    Notification::TYPE_COMMENT_REPLY,
                    $publication->id,
                );
            }
        }

        $this->notificationService->notify(
            $publication->user_id,
            $userId,
            Notification::TYPE_COMMENT,
            $publication->id,
        );

        return $publication->load([
            'comments' => fn ($q) => $q->whereNull('parent_id')->with(['author', 'media', 'replies.author', 'replies.media']),
        ]);
    }

    public function deleteComment(Comment $comment): void
    {
        $comment->delete();
    }

    private function syncMentions(Publication $publication, ?array $explicitIds = null): void
    {
        // Detectar @usernames no texto
        preg_match_all('/@([a-zA-Z0-9._]+)/', $publication->text, $matches);
        $fromText = ! empty($matches[1])
            ? User::whereIn('username', array_unique($matches[1]))->pluck('id')->all()
            : [];

        // Combinar menções do texto + explícitas
        $allIds = array_unique(array_merge($fromText, $explicitIds ?? []));

        // Filter out blocked users
        $author = User::find($publication->user_id);
        if ($author) {
            $blockedIds = $author->blockedUsers()->pluck('users.id')->all();
            $blockedByIds = $author->blockedByUsers()->pluck('users.id')->all();
            $excludeIds = array_merge($blockedIds, $blockedByIds);
            $allIds = array_diff($allIds, $excludeIds);
        }

        $publication->mentions()->sync($allIds);

        foreach ($allIds as $mentionedUserId) {
            $this->notificationService->notify(
                $mentionedUserId,
                $publication->user_id,
                Notification::TYPE_MENTION,
                $publication->id,
            );
        }
    }

    private function syncTags(Publication $publication, ?array $tags): void
    {
        if (empty($tags)) {
            return;
        }

        $publication->tags()->delete();
        $publication->tags()->createMany(
            array_map(fn (string $tag) => ['tag' => $tag], $tags)
        );
    }

    private function addMedia(Publication $publication, array $files): void
    {
        foreach ($files as $file) {
            $publication->addMedia($file)->toMediaCollection('media');
        }
    }

    private function removeMedia(Publication $publication, array $mediaIds): void
    {
        if (empty($mediaIds)) {
            return;
        }

        $publication->media()
            ->whereIn('id', $mediaIds)
            ->each(fn ($media) => $media->delete());
    }

    private function applyPrivacyFilter(Builder $query, ?int $authUserId): void
    {
        // Exclude publications from blocked/blocked-by users
        if ($authUserId) {
            $query->whereDoesntHave('author', function (Builder $a) use ($authUserId) {
                $a->whereHas('blockedUsers', fn (Builder $b) => $b->where('blocked_user_id', $authUserId))
                    ->orWhereHas('blockedByUsers', fn (Builder $b) => $b->where('user_id', $authUserId));
            });
        }

        $query->where(function (Builder $q) use ($authUserId) {
            $q->whereHas('author', fn (Builder $a) => $a->where('is_private', false));

            if ($authUserId) {
                $q->orWhere('publications.user_id', $authUserId);

                $q->orWhereExists(function ($sub) use ($authUserId) {
                    $sub->select(DB::raw(1))
                        ->from('followers')
                        ->whereColumn('followers.followed_id', 'publications.user_id')
                        ->where('followers.follower_id', $authUserId)
                        ->where('followers.status', 'accepted');
                });
            }
        });
    }

    private function applyDateFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->whereDate('publications.created_at', '>=', $filters['date_from'])
                ->whereDate('publications.created_at', '<=', $filters['date_to']);

            return;
        }

        if (empty($filters['date'])) {
            return;
        }

        match ($filters['date']) {
            'today' => $query->whereDate('publications.created_at', Carbon::today()),
            'last_24h' => $query->where('publications.created_at', '>=', Carbon::now()->subDay()),
            'last_7d' => $query->where('publications.created_at', '>=', Carbon::now()->subDays(7)),
            'last_30d' => $query->where('publications.created_at', '>=', Carbon::now()->subDays(30)),
            default => $query->whereDate('publications.created_at', $filters['date']),
        };
    }

    private function applySorting(Builder $query, string $orderBy): void
    {
        match ($orderBy) {
            'asc' => $query->oldest(),
            'popular' => $query->orderByPopularity(),
            default => $query->latest(),
        };
    }
}
