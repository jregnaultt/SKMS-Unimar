<?php

namespace App\Services;

use App\Models\DocumentVersion;
use App\Models\Production;
use App\Models\ProductionMilestone;
use App\Models\Revision;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProgressService
{
    /**
     * Get detailed progress metrics and history for a given production.
     *
     * @return array{
     *     progress_percentage: int,
     *     milestones: Collection<int, ProductionMilestone>,
     *     comments_summary: array{
     *         pending: int,
     *         in_progress: int,
     *         addressed: int
     *     },
     *     version_history: Collection<int, DocumentVersion>,
     *     timeline: Collection<int, Revision>
     * }
     */
    public function getStudentProgress(Production $production): array
    {
        // 1. Calculate progress percentage based on milestones
        $milestones = $production->milestones()->orderBy('scheduled_date', 'asc')->get();
        $totalMilestones = $milestones->count();
        $completedMilestones = $milestones->where('status', 'completed')->count();

        $progressPercentage = $totalMilestones > 0
            ? (int) round(($completedMilestones / $totalMilestones) * 100)
            : 0;

        // 2. Observations summary
        $commentsSummary = [
            'pending' => $production->comments()->whereNull('parent_id')->where('status', 'pending')->count(),
            'in_progress' => $production->comments()->whereNull('parent_id')->where('status', 'in_progress')->count(),
            'addressed' => $production->comments()->whereNull('parent_id')->where('status', 'addressed')->count(),
        ];

        // 3. Document versions history
        $versionHistory = $production->documentVersions()
            ->with('user')
            ->orderBy('id', 'desc')
            ->get();

        // 4. Revision/timeline history
        $timeline = $production->revisions()
            ->with('user')
            ->orderBy('id', 'desc')
            ->get();

        return [
            'progress_percentage' => $progressPercentage,
            'milestones' => $milestones,
            'comments_summary' => $commentsSummary,
            'version_history' => $versionHistory,
            'timeline' => $timeline,
        ];
    }

    /**
     * Get paginated and filtered productions data for the coordinator dashboard.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getCoordinatorDashboardData(array $filters = []): LengthAwarePaginator
    {
        $query = Production::query()
            ->where('workflow_state', '!=', 'published')
            ->with(['users', 'academicProgram', 'researchLine', 'academicPeriod', 'milestones']);

        // Filter by Academic Program
        if (! empty($filters['academic_program_id'])) {
            $query->where('academic_program_id', $filters['academic_program_id']);
        }

        // Filter by Research Line
        if (! empty($filters['research_line_id'])) {
            $query->where('research_line_id', $filters['research_line_id']);
        }

        // Filter by Workflow State
        if (! empty($filters['workflow_state'])) {
            $query->where('workflow_state', $filters['workflow_state']);
        }

        // Filter by Assigned Tutor
        if (! empty($filters['tutor_id'])) {
            $query->whereHas('users', function ($q) use ($filters) {
                $q->where('users.id', $filters['tutor_id'])
                    ->where('production_user.role', 'tutor');
            });
        }

        // Search by title or student name
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('users', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%")
                            ->where('production_user.role', 'author');
                    });
            });
        }

        return $query->latest()->paginate(15)->through(function ($production) {
            // Calculate progress percentage on the fly to append to each model
            $total = $production->milestones->count();
            $completed = $production->milestones->where('status', 'completed')->count();
            $production->progress_percentage = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

            return $production;
        });
    }

    /**
     * Bulk configure or update milestone dates for a specific production.
     *
     * @param array<int, array{
     *     id?: int,
     *     type: string,
     *     title: string,
     *     scheduled_date: string,
     *     status?: string
     * }> $milestonesData
     */
    public function configureMilestonesForProduction(Production $production, array $milestonesData): void
    {
        DB::transaction(function () use ($production, $milestonesData) {
            foreach ($milestonesData as $data) {
                if (isset($data['id'])) {
                    $milestone = ProductionMilestone::where('production_id', $production->id)
                        ->find($data['id']);

                    if ($milestone) {
                        $milestone->update([
                            'type' => $data['type'],
                            'title' => $data['title'],
                            'scheduled_date' => $data['scheduled_date'],
                            'status' => $data['status'] ?? $milestone->status,
                        ]);
                    }
                } else {
                    ProductionMilestone::create([
                        'production_id' => $production->id,
                        'type' => $data['type'],
                        'title' => $data['title'],
                        'scheduled_date' => $data['scheduled_date'],
                        'status' => $data['status'] ?? 'pending',
                    ]);
                }
            }
        });
    }
}
