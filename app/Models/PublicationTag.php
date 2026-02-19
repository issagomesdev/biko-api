<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicationTag extends Model
{
    protected $fillable = [
        'publication_id',
        'tag',
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }
}
