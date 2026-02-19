<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    const TYPE_LIKE = 'like';

    const TYPE_COMMENT = 'comment';

    const TYPE_FOLLOW = 'follow';

    const TYPE_MENTION = 'mention';

    const TYPE_FOLLOW_REQUEST = 'follow_request';

    const TYPE_REVIEW = 'review';

    const TYPE_REVIEW_REPLY = 'review_reply';

    const TYPE_COMMENT_REPLY = 'comment_reply';

    const TYPE_MESSAGE = 'message';

    protected $fillable = [
        'user_id',
        'sender_id',
        'type',
        'publication_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')->withTrashed();
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    // Scopes

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        if (! $type) {
            return $query;
        }

        return $query->where('type', $type);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
