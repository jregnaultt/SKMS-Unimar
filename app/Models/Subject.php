<?php

namespace App\Models;

use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasAuditLog;

    protected $guarded = ['id'];

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function subjectTutorPeriods(): HasMany
    {
        return $this->hasMany(SubjectTutorPeriod::class);
    }

    public function periodMilestones(): HasMany
    {
        return $this->hasMany(PeriodMilestone::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }
}
