<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Enrollment extends Model
{
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

            if (! $production) {
                \DB::transaction(function () use ($enrollment, $student, $tutor) {
                    $programId = AcademicProgram::first()?->id ?? 1;
                    $lineId = ResearchLine::where('academic_program_id', $programId)->first()?->id
                        ?? ResearchLine::first()?->id
                        ?? 1;
                    $typeId = ProductionType::first()?->id ?? 1;

                    // Create placeholder production
                    $production = Production::create([
                        'uuid' => (string) Str::uuid(),
                        'title' => 'Proyecto de Investigación: '.($enrollment->subject?->name ?? 'Materia').' - '.$student->name,
                        'abstract' => 'Borrador inicial del proyecto creado por la coordinación.',
                        'authors' => $student->name,
                        'tutor' => $tutor ? $tutor->name : 'Sin asignar',
                        'academic_program_id' => $programId,
                        'research_line_id' => $lineId,
                        'production_type_id' => $typeId,
                        'academic_period_id' => $enrollment->academic_period_id,
                        'subject_id' => $enrollment->subject_id,
                        'workflow_state' => 'draft',
                    ]);

                    // Attach student
                    $production->users()->attach($student->id, ['role' => 'author']);

                    // Attach tutor
                    if ($tutor) {
                        $production->users()->attach($tutor->id, ['role' => 'tutor']);
                    }

                    // Copy milestones
                    $periodMilestones = PeriodMilestone::where('academic_period_id', $production->academic_period_id)
                        ->where('subject_id', $production->subject_id)
                        ->where(function ($query) use ($tutor) {
                            $query->whereNull('tutor_id');
                            if ($tutor) {
                                $query->orWhere('tutor_id', $tutor->id);
                            }
                        })
                        ->get();

                    foreach ($periodMilestones as $pm) {
                        if ($pm->student_id && $pm->student_id !== $student->id) {
                            continue;
                        }
                        if (is_array($pm->excluded_student_ids) && in_array($student->id, $pm->excluded_student_ids)) {
                            continue;
                        }
                        ProductionMilestone::create([
                            'production_id' => $production->id,
                            'subject_id' => $production->subject_id,
                            'period_milestone_id' => $pm->id,
                            'type' => $pm->type,
                            'title' => $pm->title,
                            'scheduled_date' => $pm->scheduled_date,
                            'status' => 'pending',
                            'notify_tutor' => $pm->notify_tutor ?? true,
                            'notify_jury' => $pm->notify_jury ?? false,
                        ]);
                    }
                });
            } else {
                if ($tutor) {
                    $production->update(['tutor' => $tutor->name]);
                    $production->users()->wherePivot('role', 'tutor')->detach();
                    $production->users()->attach($tutor->id, ['role' => 'tutor']);
                }
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
