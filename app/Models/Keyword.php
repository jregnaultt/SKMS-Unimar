<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Keyword extends Model
{
    protected $guarded = ['id'];

    public function productions(): BelongsToMany
    {
        return $this->belongsToMany(Production::class)->withTimestamps();
    }
}
