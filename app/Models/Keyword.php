<?php

namespace App\Models;

use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Keyword extends Model
{
    use HasAuditLog;

    protected $guarded = ['id'];

    public function productions(): BelongsToMany
    {
        return $this->belongsToMany(Production::class)->withTimestamps();
    }
}
