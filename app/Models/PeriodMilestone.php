<?php

namespace App\Models;

use App\Models\Traits\HasAuditLog;
use App\Notifications\MissingProductionForMilestoneNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodMilestone extends Model
{
    use HasAuditLog;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(function (self $pm) {
            $productions = Production::where('academic_period_id', $pm->academic_period_id)
                ->where('subject_id', $pm->subject_id)
                ->get();

            foreach ($productions as $production) {
                $authorUser = $production->users()->wherePivot('role', 'author')->first();
                if ($pm->student_id && (! $authorUser || $authorUser->id != $pm->student_id)) {
                    continue;
                }

                if ($authorUser && is_array($pm->excluded_student_ids) && in_array($authorUser->id, $pm->excluded_student_ids)) {
                    ProductionMilestone::where('production_id', $production->id)
                        ->where('period_milestone_id', $pm->id)
                        ->delete();

                    continue;
                }

                $tutorUser = $production->users()->wherePivot('role', 'tutor')->first();
                if (! $pm->tutor_id || ($tutorUser && $tutorUser->id == $pm->tutor_id)) {
                    ProductionMilestone::updateOrCreate(
                        [
                            'production_id' => $production->id,
                            'period_milestone_id' => $pm->id,
                        ],
                        [
                            'subject_id' => $pm->subject_id,
                            'type' => $pm->type,
                            'title' => $pm->title,
                            'scheduled_date' => $pm->scheduled_date,
                            'notify_tutor' => $pm->notify_tutor ?? true,
                            'notify_jury' => $pm->notify_jury ?? false,
                        ]
                    );
                }
            }

            // Notify students who are enrolled in the subject but do not have a production registered yet
            $enrollments = Enrollment::where('academic_period_id', $pm->academic_period_id)
                ->where('subject_id', $pm->subject_id)
                ->get();

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                if (! $student) {
                    continue;
                }

                if ($pm->student_id && $student->id !== $pm->student_id) {
                    continue;
                }

                if (is_array($pm->excluded_student_ids) && in_array($student->id, $pm->excluded_student_ids)) {
                    continue;
                }

                $hasProduction = Production::where('academic_period_id', $pm->academic_period_id)
                    ->where('subject_id', $pm->subject_id)
                    ->whereHas('users', function ($query) use ($student) {
                        $query->where('users.id', $student->id)->where('production_user.role', 'author');
                    })
                    ->exists();

                if (! $hasProduction) {
                    $student->notify(new MissingProductionForMilestoneNotification($pm));
                }
            }
        });

        static::deleted(function (self $pm) {
            ProductionMilestone::where('period_milestone_id', $pm->id)->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'tutor_id' => 'integer',
            'scheduled_date' => 'datetime',
            'notify_tutor' => 'boolean',
            'notify_jury' => 'boolean',
            'excluded_student_ids' => 'array',
        ];
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
