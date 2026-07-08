<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\Enrollment;
use App\Models\PeriodMilestone;
use App\Models\Production;
use App\Models\ResearchLine;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProgressController extends Controller
{
    /**
     * Inject ProgressService.
     */
    public function __construct(protected ProgressService $progressService) {}

    /**
     * Redirect to student's active production progress page or show global milestones if they have no production.
     */
    public function myMilestones(Request $request): RedirectResponse|View
    {
        /** @var User $user */
        $user = auth()->user();

        // 1. Check if student has a registered production
        $production = $user->productions()->latest()->first();

        if ($production) {
            return redirect()->route('progress.student.show', $production);
        }

        // 2. Student does NOT have a production. Check their enrollment
        $enrollment = Enrollment::where('student_id', $user->id)
            ->with(['academicPeriod', 'subject'])
            ->latest()
            ->first();

        $periodMilestones = collect();
        if ($enrollment) {
            $periodMilestones = PeriodMilestone::where('academic_period_id', $enrollment->academic_period_id)
                ->where('subject_id', $enrollment->subject_id)
                ->where(function ($query) use ($user) {
                    $query->whereNull('student_id')
                        ->orWhere('student_id', $user->id);
                })
                ->orderBy('scheduled_date', 'asc')
                ->get()
                ->filter(function ($pm) use ($user) {
                    if (is_array($pm->excluded_student_ids) && in_array($user->id, $pm->excluded_student_ids)) {
                        return false;
                    }

                    return true;
                });
        }

        return view('pages.progress.no-production-milestones', [
            'enrollment' => $enrollment,
            'periodMilestones' => $periodMilestones,
        ]);
    }

    /**
     * Display student progress dashboard for a specific production.
     */
    public function studentShow(Production $production): View
    {
        Gate::authorize('viewProgress', $production);

        $progressData = $this->progressService->getStudentProgress($production);

        return view('pages.progress.student', array_merge([
            'production' => $production->load(['academicProgram', 'researchLine', 'academicPeriod']),
        ], $progressData));
    }

    /**
     * Display the coordinator progress tracking dashboard.
     */
    public function coordinatorIndex(Request $request): View
    {
        if (! auth()->user()->hasRole(['Coordinador', 'Super Admin', 'Decano'])) {
            abort(403, 'Acceso denegado. Se requieren permisos de Coordinador o Decano.');
        }

        $filters = $request->only(['academic_program_id', 'research_line_id', 'workflow_state', 'tutor_id', 'search']);

        $productions = $this->progressService->getCoordinatorDashboardData($filters);

        $programs = AcademicProgram::where('is_active', true)->orderBy('name')->get();
        $lines = ResearchLine::where('is_active', true)->orderBy('name')->get();

        // Fetch users who are tutors (Spatie role check)
        $tutors = User::role('Tutor')->orderBy('name')->get();

        return view('pages.progress.coordinator', [
            'productions' => $productions,
            'programs' => $programs,
            'lines' => $lines,
            'tutors' => $tutors,
            'filters' => $filters,
        ]);
    }

    /**
     * Configure milestone dates for a specific production.
     */
    public function configureMilestones(Request $request, Production $production): RedirectResponse
    {
        Gate::authorize('manageMilestones', $production);

        $request->validate([
            'milestones' => 'required|array',
            'milestones.*.type' => 'required|string|in:delivery,defense,pre_defense,system_defense',
            'milestones.*.title' => 'required|string|max:255',
            'milestones.*.scheduled_date' => 'required|date',
            'milestones.*.id' => 'nullable|integer|exists:production_milestones,id',
            'milestones.*.status' => 'nullable|string|in:pending,completed,missed',
        ]);

        try {
            $this->progressService->configureMilestonesForProduction($production, $request->input('milestones'));

            return back()->with('success', 'Hitos de la producción científica configurados correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al configurar hitos: '.$e->getMessage());
        }
    }
}
