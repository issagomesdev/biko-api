<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    use HasFactory;

    public $table = 'publications';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'title',
        'text',
        'user_id'
    ];

    public function User(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function User(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
