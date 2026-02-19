<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Review extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'reviewer_id',
        'stars',
        'comment',
        'parent_id',
    ];

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

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id')->withTrashed();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Review::class, 'parent_id');
    }
}
