<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ResearchLine;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    /**
     * Inject ProgressService.
     */
    public function __construct(protected ProgressService $progressService) {}

    /**
     * Display student progress dashboard for a specific production.
     */
    public function studentShow(Production $production): View
    {
        $user = auth()->user();
        $this->authorizeProgressView($production, $user);

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
        if (! auth()->user()->hasRole(['Coordinador', 'Super Admin'])) {
            abort(403, 'Acceso denegado. Se requieren permisos de Coordinador.');
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
        if (! auth()->user()->hasRole(['Coordinador', 'Super Admin'])) {
            abort(403, 'Acceso denegado. Se requieren permisos de Coordinador.');
        }

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

    /**
     * Helper to authorize viewing a specific production progress.
     */
    protected function authorizeProgressView(Production $production, User $user): void
    {
        if ($user->hasRole(['Coordinador', 'Super Admin'])) {
            return;
        }

        $isAssociated = $production->users()->where('users.id', $user->id)->exists();
        if (! $isAssociated) {
            abort(403, 'No tienes permiso para ver el progreso de esta producción científica.');
        }
    }
}
