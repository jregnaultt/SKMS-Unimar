<?php

namespace App\Models;

use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasAuditLog;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(function (self $enrollment) {
            $student = $enrollment->student;
            $tutor = $enrollment->tutor;

            $production = Production::where('academic_period_id', $enrollment->academic_period_id)
                ->where('subject_id', $enrollment->subject_id)
                ->whereHas('users', function ($query) use ($student) {
                    $query->where('users.id', $student->id)->where('production_user.role', 'author');
                })
                ->first();

            if ($production && $tutor) {
                $production->update(['tutor' => $tutor->name]);
                $production->users()->wherePivot('role', 'tutor')->detach();
                $production->users()->attach($tutor->id, ['role' => 'tutor']);
            }
        });
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
