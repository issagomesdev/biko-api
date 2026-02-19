<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, InteractsWithMedia, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'description',
        'is_private',
        'last_seen_at',
        'password',
        'city_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_private' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    // Media

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->nonQueued()
            ->performOnCollections('avatar');

        $this->addMediaConversion('thumb')
            ->width(600)
            ->height(200)
            ->nonQueued()
            ->performOnCollections('cover');
    }

    // Scopes

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        $terms = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere('users.name', 'LIKE', "%{$term}%")
                    ->orWhereHas('categories', fn (Builder $q) => $q->where('name', 'LIKE', "%{$term}%"))
                    ->orWhereHas('city', fn (Builder $q) => $q->where('name', 'LIKE', "%{$term}%"))
                    ->orWhereHas('city.state', fn (Builder $q) => $q->where('name', 'LIKE', "%{$term}%"));
            }
        });
    }

    public function scopeInCategories(Builder $query, ?array $categories): Builder
    {
        if (empty($categories)) {
            return $query;
        }

        return $query->whereHas('categories', function (Builder $q) use ($categories) {
            $q->whereIn('category_id', $categories);
        });
    }

    public function scopeInCity(Builder $query, ?int $cityId): Builder
    {
        if (is_null($cityId)) {
            return $query;
        }

        return $query->where('city_id', $cityId);
    }

    public function scopeOrderByPopularity(Builder $query): Builder
    {
        return $query->withCount('followers')
            ->orderBy('followers_count', 'DESC')
            ->orderBy('created_at', 'DESC');
    }

    // Relationships

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'users_categories', 'user_id', 'category_id');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class, 'user_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'followed_id', 'follower_id')
            ->wherePivot('status', 'accepted');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'followed_id')
            ->wherePivot('status', 'accepted');
    }

    public function pendingFollowers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'followed_id', 'follower_id')
            ->wherePivot('status', 'pending');
    }

    public function pendingFollowing(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'followed_id')
            ->wherePivot('status', 'pending');
    }

    public function isFollowedBy(int $userId): bool
    {
        return $this->followers()->where('follower_id', $userId)->exists();
    }

    public function hasPendingFollowFrom(int $userId): bool
    {
        return $this->pendingFollowers()->where('follower_id', $userId)->exists();
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'mentions')->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocks', 'user_id', 'blocked_user_id');
    }

    public function blockedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocked_user_id', 'user_id');
    }

    public function hasBlocked(int $userId): bool
    {
        return $this->blockedUsers()->where('blocked_user_id', $userId)->exists();
    }

    public function isBlockedBy(int $userId): bool
    {
        return $this->blockedByUsers()->where('user_id', $userId)->exists();
    }

    public function conversations()
    {
        return Conversation::query()->forUser($this->id);
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->greaterThan(now()->subMinutes(5));
    }

    public function averageRating(): ?float
    {
        $avg = $this->reviews()->root()->avg('stars');

        return $avg ? round((float) $avg, 1) : null;
    }
}
