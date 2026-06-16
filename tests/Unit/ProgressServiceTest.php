<?php

namespace Tests\Unit;

use App\Models\AcademicProgram;
use App\Models\Comment;
use App\Models\DocumentVersion;
use App\Models\Production;
use App\Models\ProductionMilestone;
use App\Models\ResearchLine;
use App\Models\Revision;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProgressService;
    }

    /**
     * Test progress calculation and aggregation for a student.
     */
    public function test_it_calculates_student_progress_percentage_and_summarizes_correctly(): void
    {
        $production = Production::factory()->create();

        // 1. Progress calculation with no milestones should be 0%
        $progress = $this->service->getStudentProgress($production);
        $this->assertEquals(0, $progress['progress_percentage']);
        $this->assertCount(0, $progress['milestones']);

        // 2. Progress with completed and pending milestones
        ProductionMilestone::factory()->completed()->create([
            'production_id' => $production->id,
            'title' => 'Milestone 1',
        ]);
        ProductionMilestone::factory()->create([
            'production_id' => $production->id,
            'title' => 'Milestone 2',
            'status' => 'pending',
        ]);

        // Completed 1 out of 2 = 50%
        $progress = $this->service->getStudentProgress($production);
        $this->assertEquals(50, $progress['progress_percentage']);
        $this->assertCount(2, $progress['milestones']);

        // 3. Comments summary aggregation
        Comment::factory()->pending()->create(['production_id' => $production->id]);
        Comment::factory()->inProgress()->create(['production_id' => $production->id]);
        Comment::factory()->addressed()->create(['production_id' => $production->id]);
        // Add a reply comment which should NOT count towards root observations
        Comment::factory()->pending()->create([
            'production_id' => $production->id,
            'parent_id' => Comment::first()->id,
        ]);

        $progress = $this->service->getStudentProgress($production);
        $this->assertEquals(1, $progress['comments_summary']['pending']);
        $this->assertEquals(1, $progress['comments_summary']['in_progress']);
        $this->assertEquals(1, $progress['comments_summary']['addressed']);
    }

    /**
     * Test history collections loading (versions, timeline/revisions).
     */
    public function test_it_loads_version_and_revision_history_in_correct_order(): void
    {
        $production = Production::factory()->create();
        $user = User::factory()->create();

        // Revisions (timeline)
        $revision1 = Revision::create([
            'production_id' => $production->id,
            'user_id' => $user->id,
            'previous_state' => 'draft',
            'new_state' => 'under_review',
            'comment' => 'Envío inicial',
        ]);

        $revision2 = Revision::create([
            'production_id' => $production->id,
            'user_id' => $user->id,
            'previous_state' => 'under_review',
            'new_state' => 'needs_corrections',
            'comment' => 'Correcciones solicitadas',
        ]);

        // Versions
        $version1 = DocumentVersion::create([
            'production_id' => $production->id,
            'user_id' => $user->id,
            'version_number' => 1,
            'changelog' => 'Versión inicial',
        ]);

        $version2 = DocumentVersion::create([
            'production_id' => $production->id,
            'user_id' => $user->id,
            'version_number' => 2,
            'changelog' => 'Corrección del capítulo 2',
        ]);

        $progress = $this->service->getStudentProgress($production);

        // Assert latest is first
        $this->assertCount(2, $progress['timeline']);
        $this->assertEquals($revision2->id, $progress['timeline']->first()->id);

        $this->assertCount(2, $progress['version_history']);
        $this->assertEquals($version2->id, $progress['version_history']->first()->id);
    }

    /**
     * Test coordinator dashboard queries and filters.
     */
    public function test_coordinator_dashboard_filters(): void
    {
        $program1 = AcademicProgram::factory()->create();
        $program2 = AcademicProgram::factory()->create();

        $line1 = ResearchLine::factory()->create(['academic_program_id' => $program1->id]);
        $line2 = ResearchLine::factory()->create(['academic_program_id' => $program2->id]);

        $production1 = Production::factory()->create([
            'title' => 'Tesis de Redes de Sensores',
            'academic_program_id' => $program1->id,
            'research_line_id' => $line1->id,
            'workflow_state' => 'under_review',
        ]);

        $production2 = Production::factory()->create([
            'title' => 'Análisis de Productividad',
            'academic_program_id' => $program2->id,
            'research_line_id' => $line2->id,
            'workflow_state' => 'approved',
        ]);

        $student = User::factory()->create(['name' => 'Luis Gomez']);
        $production1->users()->attach($student->id, ['role' => 'author']);

        // 1. No filters: both returned
        $results = $this->service->getCoordinatorDashboardData();
        $this->assertCount(2, $results->items());

        // 2. Filter by Academic Program
        $results = $this->service->getCoordinatorDashboardData(['academic_program_id' => $program1->id]);
        $this->assertCount(1, $results->items());
        $this->assertEquals($production1->id, $results->first()->id);

        // 3. Filter by Research Line
        $results = $this->service->getCoordinatorDashboardData(['research_line_id' => $line2->id]);
        $this->assertCount(1, $results->items());
        $this->assertEquals($production2->id, $results->first()->id);

        // 4. Filter by Workflow State
        $results = $this->service->getCoordinatorDashboardData(['workflow_state' => 'approved']);
        $this->assertCount(1, $results->items());
        $this->assertEquals($production2->id, $results->first()->id);

        // 5. Search filter (by title)
        $results = $this->service->getCoordinatorDashboardData(['search' => 'Sensores']);
        $this->assertCount(1, $results->items());
        $this->assertEquals($production1->id, $results->first()->id);

        // 6. Search filter (by student name)
        $results = $this->service->getCoordinatorDashboardData(['search' => 'Luis Gomez']);
        $this->assertCount(1, $results->items());
        $this->assertEquals($production1->id, $results->first()->id);
    }

    /**
     * Test bulk milestones configuration/creation/updating.
     */
    public function test_configure_milestones_for_production(): void
    {
        $production = Production::factory()->create();

        // 1. Create milestones
        $milestonesData = [
            [
                'type' => 'delivery',
                'title' => 'Entregable 1',
                'scheduled_date' => '2026-07-01 12:00:00',
            ],
            [
                'type' => 'pre_defense',
                'title' => 'Pre-Defensa',
                'scheduled_date' => '2026-08-01 09:00:00',
            ],
        ];

        $this->service->configureMilestonesForProduction($production, $milestonesData);

        $this->assertDatabaseCount('production_milestones', 2);
        $this->assertDatabaseHas('production_milestones', [
            'production_id' => $production->id,
            'title' => 'Entregable 1',
            'type' => 'delivery',
            'status' => 'pending',
        ]);

        // 2. Update existing and create new
        $existing = ProductionMilestone::first();
        $updateData = [
            [
                'id' => $existing->id,
                'type' => 'delivery',
                'title' => 'Entregable 1 Actualizado',
                'scheduled_date' => '2026-07-05 12:00:00',
                'status' => 'completed',
            ],
            [
                'type' => 'defense',
                'title' => 'Defensa Final',
                'scheduled_date' => '2026-09-01 14:00:00',
            ],
        ];

        $this->service->configureMilestonesForProduction($production, $updateData);

        // Total should be 3 now (2 original, 1 updated, 1 new created)
        $this->assertDatabaseCount('production_milestones', 3);
        $this->assertDatabaseHas('production_milestones', [
            'id' => $existing->id,
            'title' => 'Entregable 1 Actualizado',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('production_milestones', [
            'production_id' => $production->id,
            'title' => 'Defensa Final',
            'type' => 'defense',
        ]);
    }
}
