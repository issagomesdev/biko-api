<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Publication extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    const TYPE_CLIENT = 0;

    const TYPE_PROVIDER = 1;

    protected $fillable = [
        'text',
        'type',
        'city_id',
        'user_id',
    ];

    protected $casts = [
        'type' => 'integer',
    ];

    // Relationships

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'publications_categories', 'publication_id', 'category_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(PublicationTag::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mentions')->withTimestamps();
    }

    // Media

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('media')
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->nonQueued()
            ->performOnCollections('media');
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
                $q->orWhere('publications.text', 'LIKE', "%{$term}%")
                    ->orWhereHas('tags', fn (Builder $q) => $q->where('tag', 'LIKE', "%{$term}%"))
                    ->orWhereHas('categories', fn (Builder $q) => $q->where('name', 'LIKE', "%{$term}%"))
                    ->orWhereHas('city', fn (Builder $q) => $q->where('name', 'LIKE', "%{$term}%"))
                    ->orWhereHas('city.state', fn (Builder $q) => $q->where('name', 'LIKE', "%{$term}%"));
            }
        });
    }

    public function scopeOfType(Builder $query, ?int $type): Builder
    {
        if (is_null($type)) {
            return $query;
        }

        return $query->where('type', $type);
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

    public function scopeWithTags(Builder $query, ?array $tags): Builder
    {
        if (empty($tags)) {
            return $query;
        }

        return $query->whereHas('tags', function (Builder $q) use ($tags) {
            $q->whereIn('tag', $tags);
        });
    }

    public function scopeOrderByPopularity(Builder $query): Builder
    {
        return $query->withCount(['likes', 'comments'])
            ->orderByRaw('((likes_count + comments_count) / 2) DESC')
            ->orderBy('comments_count', 'DESC')
            ->orderBy('created_at', 'ASC');
    }
}
