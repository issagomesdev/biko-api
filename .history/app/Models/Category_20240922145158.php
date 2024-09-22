<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public $table = 'categories';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name'
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'publications_categories', 'category_id', 'publication_id');
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'publications_categories', 'category_id', 'publication_id');
    }
}
