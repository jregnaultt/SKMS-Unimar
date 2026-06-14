<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DocumentVersion extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    /**
     * Register media collections for document versions.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documento_version')
            ->singleFile();
    }

    /**
     * Get the production associated with the version.
     */
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    /**
     * Get the user who uploaded this version.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the milestones associated with this version.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(ProductionMilestone::class);
    }
}
