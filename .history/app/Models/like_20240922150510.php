<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    public $table = 'likes';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'comment',
        'publication_id',
        'user_id'
    ];

    public function author(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function publication(): HasOne
    {
        return $this->hasOne(Publication::class);
    }
}
