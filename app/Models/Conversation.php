<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['user_one_id', 'user_two_id'];

    // Relationships

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // Scopes

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
    }

    // Helpers

    public function getOtherUser(int $userId): BelongsTo
    {
        return $this->user_one_id === $userId ? $this->userTwo() : $this->userOne();
    }

    public function getOtherUserId(int $userId): int
    {
        return $this->user_one_id === $userId ? $this->user_two_id : $this->user_one_id;
    }

    public static function findBetween(int $userA, int $userB): ?self
    {
        [$min, $max] = [min($userA, $userB), max($userA, $userB)];

        return static::where('user_one_id', $min)->where('user_two_id', $max)->first();
    }
}
