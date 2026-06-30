<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Production;
use App\Models\ProductionMilestone;
use App\Models\ResearchLine;
use App\Models\User;
use App\Services\ProductionClaimService;
use App\Services\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ProgressService $progressService,
        protected ProductionClaimService $claimService
    ) {}

    /**
     * Display the dynamic role-based dashboard.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = auth()->user();

        // Get actual roles from Spatie
        $roles = $user->getRoleNames()->toArray();

        if (empty($roles)) {
            $roles = ['Estudiante']; // Fallback
        }

        // Determine active role from session, or default to the first one
        $activeRole = session('active_dashboard_role');
        if (! $activeRole || ! in_array($activeRole, $roles)) {
            $activeRole = $roles[0];
            session(['active_dashboard_role' => $activeRole]);
        }

        $data = [];

        // Eager load data based on the active role
        switch ($activeRole) {
            case 'Estudiante':
                $data = $this->getStudentData($user);
                break;
            case 'Tutor':
            case 'Jurado':
                $data = $this->getEvaluatorData($user, $activeRole);
                break;
            case 'Coordinador':
                $data = $this->getCoordinatorData($request);
                break;
            case 'Super Admin':
                $data = $this->getAdminData();
                break;
        }

        return view('dashboard', [
            'user' => $user,
            'roles' => $roles,
            'activeRole' => $activeRole,
            'data' => $data,
        ]);
    }

    /**
     * Switch the active dashboard role.
     */
    public function switchRole(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => 'required|string',
        ]);

        /** @var User $user */
        $user = auth()->user();
        $targetRole = $request->input('role');

        if ($user->hasRole($targetRole)) {
            session(['active_dashboard_role' => $targetRole]);

            return redirect()->route('dashboard')->with('success', "Rol cambiado a {$targetRole} correctamente.");
        }

        return redirect()->route('dashboard')->with('error', 'No tienes permiso para acceder a ese rol.');
    }

    /**
     * Get data for Student Dashboard.
     */
    protected function getStudentData(User $user): array
    {
        $myProductions = $user->productions()->latest()->get();
        $suggestedProductions = $this->claimService->suggestHistoricalProductions($user);

        $activeProduction = $myProductions->first();
        $progressData = [];

        if ($activeProduction) {
            $progressData = $this->progressService->getStudentProgress($activeProduction);

            // Fetch top-level comments for active production with replies
            $progressData['comments'] = Comment::where('production_id', $activeProduction->id)
                ->whereNull('parent_id')
                ->with(['user', 'replies.user'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return [
            'myProductions' => $myProductions,
            'suggestedProductions' => $suggestedProductions,
            'activeProduction' => $activeProduction,
            'progressData' => $progressData,
        ];
    }

    /**
     * Get data for Tutor / Jury Dashboard.
     */
    protected function getEvaluatorData(User $user, string $activeRole): array
    {
        // Get productions where user is assigned as tutor or jury
        $productions = $user->productions()
            ->wherePivot('role', strtolower($activeRole))
            ->with(['academicProgram', 'academicPeriod', 'users'])
            ->latest()
            ->get();

        // Get defense milestones related to these productions
        $productionIds = $productions->pluck('id')->toArray();
        $defensas = ProductionMilestone::whereIn('production_id', $productionIds)
            ->whereIn('type', ['defense', 'pre_defense'])
            ->with('production')
            ->orderBy('scheduled_date', 'asc')
            ->get();

        return [
            'productions' => $productions,
            'defensas' => $defensas,
            'roleLabel' => $activeRole,
        ];
    }

    /**
     * Get data for Coordinator Dashboard.
     */
    protected function getCoordinatorData(Request $request): array
    {
        // Metric counts
        $metrics = [
            'total' => Production::count(),
            'draft' => Production::where('workflow_state', 'draft')->count(),
            'under_review' => Production::where('workflow_state', 'under_review')->count(),
            'needs_corrections' => Production::where('workflow_state', 'needs_corrections')->count(),
            'approved' => Production::where('workflow_state', 'approved')->count(),
            'published' => Production::where('workflow_state', 'published')->count(),
            'rejected' => Production::where('workflow_state', 'rejected')->count(),
        ];

        // Dublin Core validation queue (approved but not yet published)
        $validationQueue = Production::where('workflow_state', 'approved')
            ->with(['users', 'academicProgram', 'researchLine', 'productionType', 'academicPeriod'])
            ->latest()
            ->get();

        // Consolidated student monitoring list (using ProgressService)
        $filters = $request->only(['academic_program_id', 'research_line_id', 'workflow_state', 'tutor_id', 'search']);
        $paginatedProductions = $this->progressService->getCoordinatorDashboardData($filters);

        $programs = AcademicProgram::where('is_active', true)->orderBy('name')->get();
        $lines = ResearchLine::where('is_active', true)->orderBy('name')->get();
        $tutors = User::role('Tutor')->orderBy('name')->get();

        return [
            'metrics' => $metrics,
            'validationQueue' => $validationQueue,
            'paginatedProductions' => $paginatedProductions,
            'programs' => $programs,
            'lines' => $lines,
            'tutors' => $tutors,
            'filters' => $filters,
        ];
    }

    /**
     * Get data for Admin Dashboard.
     */
    protected function getAdminData(): array
    {
        // 10 most recent audit logs
        $auditLogs = AuditLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        return [
            'auditLogs' => $auditLogs,
        ];
    }
}
