<?php

namespace App\Models;

use App\Jobs\SyncMilestoneToGoogleCalendarJob;
use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMilestone extends Model
{
    use HasAuditLog, HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(function (self $milestone) {
            if ($milestone->scheduled_date) {
                dispatch(new SyncMilestoneToGoogleCalendarJob($milestone, 'sync'));
            }
        });

        static::deleted(function (self $milestone) {
            if ($milestone->google_event_id) {
                dispatch(new SyncMilestoneToGoogleCalendarJob($milestone, 'delete'));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'datetime',
            'completed_date' => 'datetime',
            'notify_tutor' => 'boolean',
            'notify_jury' => 'boolean',
        ];
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class);
    }

    public function periodMilestone(): BelongsTo
    {
        return $this->belongsTo(PeriodMilestone::class);
    }
}
