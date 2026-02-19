<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}
    public function list(array $filters = [], ?int $authUserId = null): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['categories', 'city', 'media'])
            ->withCount(['followers', 'following', 'reviews' => fn ($q) => $q->whereNull('parent_id')])
            ->search($filters['search'] ?? null)
            ->inCategories($filters['categories'] ?? null)
            ->inCity(isset($filters['city_id']) ? (int) $filters['city_id'] : null);

        if ($authUserId) {
            $query->whereDoesntHave('blockedUsers', fn (Builder $q) => $q->where('blocked_user_id', $authUserId))
                ->whereDoesntHave('blockedByUsers', fn (Builder $q) => $q->where('user_id', $authUserId));
        }

        $this->applySorting($query, $filters['orderBy'] ?? 'popular');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function findWithRelations(User $user): User
    {
        return $user->load('categories', 'city', 'media')->loadCount(['followers', 'following', 'reviews' => fn ($q) => $q->whereNull('parent_id')]);
    }

    public function blockUser(User $user, int $blockedUserId): void
    {
        $user->blockedUsers()->syncWithoutDetaching([$blockedUserId]);
    }

    public function unblockUser(User $user, int $blockedUserId): void
    {
        $user->blockedUsers()->detach($blockedUserId);
    }

    public function listBlockedUsers(User $user): LengthAwarePaginator
    {
        return $user->blockedUsers()
            ->with(['media'])
            ->paginate(20);
    }

    public function toggleFollow(User $user, int $followerId): array
    {
        if ($user->hasBlocked($followerId) || $user->isBlockedBy($followerId)) {
            abort(403, 'Blocked.');
        }

        $existing = DB::table('followers')
            ->where('followed_id', $user->id)
            ->where('follower_id', $followerId)
            ->first();

        if ($existing) {
            DB::table('followers')
                ->where('followed_id', $user->id)
                ->where('follower_id', $followerId)
                ->delete();

            return ['status' => $existing->status === 'pending' ? 'cancelled' : 'unfollowed'];
        }

        if ($user->is_private) {
            DB::table('followers')->insert([
                'follower_id' => $followerId,
                'followed_id' => $user->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->notificationService->notify(
                $user->id,
                $followerId,
                Notification::TYPE_FOLLOW_REQUEST,
            );

            return ['status' => 'requested'];
        }

        DB::table('followers')->insert([
            'follower_id' => $followerId,
            'followed_id' => $user->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->notificationService->notify(
            $user->id,
            $followerId,
            Notification::TYPE_FOLLOW,
        );

        return ['status' => 'followed'];
    }

    public function acceptFollowRequest(User $user, int $followerId): void
    {
        if ($user->hasBlocked($followerId) || $user->isBlockedBy($followerId)) {
            abort(403, 'Blocked.');
        }

        DB::table('followers')
            ->where('followed_id', $user->id)
            ->where('follower_id', $followerId)
            ->where('status', 'pending')
            ->update(['status' => 'accepted', 'updated_at' => now()]);

        $this->notificationService->notify(
            $followerId,
            $user->id,
            Notification::TYPE_FOLLOW,
        );
    }

    public function rejectFollowRequest(User $user, int $followerId): void
    {
        DB::table('followers')
            ->where('followed_id', $user->id)
            ->where('follower_id', $followerId)
            ->where('status', 'pending')
            ->delete();
    }

    public function listPendingFollowers(User $user): LengthAwarePaginator
    {
        return $user->pendingFollowers()
            ->with(['categories', 'city', 'media'])
            ->withCount(['followers', 'following'])
            ->paginate(20);
    }

    public function update(
        User $user,
        array $data,
        ?array $categoryIds = null,
        ?UploadedFile $avatar = null,
        ?UploadedFile $cover = null,
        bool $removeAvatar = false,
        bool $removeCover = false,
    ): User {
        $user->update($data);

        // Auto-accept pending followers when switching to public
        if (array_key_exists('is_private', $data) && ! $data['is_private']) {
            $pendingFollowerIds = DB::table('followers')
                ->where('followed_id', $user->id)
                ->where('status', 'pending')
                ->pluck('follower_id');

            if ($pendingFollowerIds->isNotEmpty()) {
                DB::table('followers')
                    ->where('followed_id', $user->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'accepted', 'updated_at' => now()]);

                foreach ($pendingFollowerIds as $followerId) {
                    $this->notificationService->notify(
                        $followerId,
                        $user->id,
                        Notification::TYPE_FOLLOW,
                    );
                }
            }
        }

        if (! is_null($categoryIds)) {
            $user->categories()->sync($categoryIds);
        }

        if ($removeAvatar) {
            $user->clearMediaCollection('avatar');
        } elseif ($avatar) {
            $user->addMedia($avatar)->toMediaCollection('avatar');
        }

        if ($removeCover) {
            $user->clearMediaCollection('cover');
        } elseif ($cover) {
            $user->addMedia($cover)->toMediaCollection('cover');
        }

        return $user->load('categories', 'city', 'media')->loadCount(['followers', 'following', 'reviews' => fn ($q) => $q->whereNull('parent_id')]);
    }

    public function deleteAccount(User $user): void
    {
        // Revoke all tokens
        $user->tokens()->delete();

        // Remove avatar and cover
        $user->clearMediaCollection('avatar');
        $user->clearMediaCollection('cover');

        // Soft delete (keep email/password for recovery login)
        $user->delete();
    }

    public function restoreAccount(User $user): void
    {
        $user->restore();
    }

    public function permanentlyDeleteExpired(): int
    {
        $users = User::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(60))
            ->get();

        foreach ($users as $user) {
            // Anonymize before permanent deletion
            $user->update([
                'name' => 'Usuário deletado',
                'username' => 'deleted_' . $user->id,
                'email' => "deleted_{$user->id}@deleted.com",
                'phone' => null,
                'description' => null,
            ]);
            $user->forceDelete();
        }

        return $users->count();
    }

    private function applySorting(Builder $query, string $orderBy): void
    {
        match ($orderBy) {
            'asc' => $query->oldest(),
            'desc' => $query->latest(),
            default => $query->orderByPopularity(),
        };
    }
}
