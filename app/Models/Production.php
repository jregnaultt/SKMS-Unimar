<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Production extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $guarded = ['id'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documento')
            ->singleFile();
    }

    protected function casts(): array
    {
        return [
            'submission_date' => 'datetime',
            'approval_date' => 'datetime',
        ];
    }

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class);
    }

    public function researchLine(): BelongsTo
    {
        return $this->belongsTo(ResearchLine::class);
    }

    public function productionType(): BelongsTo
    {
        return $this->belongsTo(ProductionType::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class)->withTimestamps();
    }

    public function claims(): HasMany
    {
        return $this->hasMany(ProductionClaim::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProductionMilestone::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class);
    }

    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
