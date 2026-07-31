<?php

namespace App\Models;

use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Production extends Model implements HasMedia
{
    use HasAuditLog, HasFactory, InteractsWithMedia, SoftDeletes;

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
            'published_at' => 'datetime',
            'jury_review_requested' => 'boolean',
        ];
    }

    /**
     * Scope to only published productions exposed by OAI-PMH and reports.
     */
    public function scopePublished($query): Builder
    {
        return $query->where('workflow_state', 'published');
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
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

    public function preassignedJury1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preassigned_jury_1_id');
    }

    public function preassignedJury2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preassigned_jury_2_id');
    }

    /**
     * Get the appropriate show URL based on the workflow state.
     */
    public function getShowUrlAttribute(): string
    {
        if (auth()->check()) {
            return route('productions.show', $this);
        }

        if ($this->workflow_state === 'published') {
            return route('catalog.show-public', $this->uuid);
        }

        return route('productions.show', $this);
    }

    /**
     * Get the appropriate PDF download URL based on the workflow state.
     */
    public function getPdfUrlAttribute(): string
    {
        if ($this->workflow_state === 'published') {
            return route('catalog.download-public-pdf', $this->uuid);
        }

        return route('productions.document', $this);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the human-readable label for a given workflow state.
     */
    public static function getStatusLabelFor(string $state): string
    {
        $statusLabels = [
            'draft' => 'Borrador',
            'under_review' => 'En Revisión',
            'under_tutor_review' => 'En Revisión (Tutor)',
            'under_jury_review' => 'En Revisión (Jurado)',
            'under_coordinator_review' => 'En Revisión (Coordinación)',
            'needs_corrections' => 'Requiere Correcciones',
            'approved' => 'Aprobado',
            'published' => 'Publicado',
            'rejection_proposed' => 'Propuesta de Rechazo',
            'rejected' => 'Rechazado',
        ];

        return $statusLabels[$state] ?? $state;
    }

    /**
     * Get the human-readable label for the current workflow state.
     */
    public function getStatusLabel(): string
    {
        return self::getStatusLabelFor($this->workflow_state);
    }

    /**
     * Get the CSS color class for the current workflow state badge.
     */
    public function getStatusColorClass(): string
    {
        $statusColors = [
            'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
            'under_review' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
            'under_tutor_review' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
            'under_jury_review' => 'bg-purple-50 text-purple-800 border-purple-200',
            'under_coordinator_review' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
            'needs_corrections' => 'bg-orange-50 text-orange-850 border-orange-200',
            'approved' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'published' => 'bg-blue-50 text-blue-800 border-blue-200',
            'rejection_proposed' => 'bg-pink-50 text-pink-850 border-pink-200',
            'rejected' => 'bg-rose-50 text-rose-800 border-rose-200',
        ];

        return $statusColors[$this->workflow_state] ?? 'bg-slate-105 text-slate-800 border-slate-200';
    }

    /**
     * Get the CSS class for the status dot indicator in tables.
     */
    public function getStatusBulletColorClass(): string
    {
        return match ($this->workflow_state) {
            'approved', 'published' => 'bg-emerald-500',
            'under_review', 'under_tutor_review', 'under_coordinator_review', 'needs_corrections' => 'bg-amber-500',
            'under_jury_review' => 'bg-purple-500',
            'rejection_proposed' => 'bg-pink-500',
            'rejected' => 'bg-rose-500',
            default => 'bg-slate-400',
        };
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Production $production) {
            if (empty($production->uuid)) {
                $production->uuid = (string) Str::uuid();
            }
        });
    }
}
