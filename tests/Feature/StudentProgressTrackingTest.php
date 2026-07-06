<?php

namespace Tests\Feature;

use App\Models\Production;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentProgressTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $coordinator;

    private User $outsider;

    private Production $production;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Tutor']);
        Role::firstOrCreate(['name' => 'Jurado']);

        // 2. Setup Users
        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');

        $this->outsider = User::factory()->create();
        $this->outsider->assignRole('Estudiante');

        // 3. Setup Production
        $this->production = Production::factory()->create();
        $this->production->users()->attach($this->student->id, ['role' => 'author']);
    }

    /**
     * Test student can view their own progress dashboard.
     */
    public function test_student_can_view_own_progress_dashboard(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('progress.student.show', $this->production));

        $response->assertStatus(200);
        $response->assertViewIs('pages.progress.student');
        $response->assertViewHasAll([
            'production',
            'progress_percentage',
            'milestones',
            'comments_summary',
            'version_history',
            'timeline',
        ]);
    }

    /**
     * Test student cannot view another student's progress dashboard.
     */
    public function test_student_cannot_view_another_student_progress_dashboard(): void
    {
        $response = $this->actingAs($this->outsider)
            ->get(route('progress.student.show', $this->production));

        $response->assertStatus(403);
    }

    /**
     * Test coordinator can view any student's progress dashboard.
     */
    public function test_coordinator_can_view_any_student_progress_dashboard(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->get(route('progress.student.show', $this->production));

        $response->assertStatus(200);
        $response->assertViewIs('pages.progress.student');
    }

    /**
     * Test coordinator can view coordinator tracking dashboard.
     */
    public function test_coordinator_can_view_coordinator_dashboard(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->get(route('progress.coordinator.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.progress.coordinator');
        $response->assertViewHasAll([
            'productions',
            'programs',
            'lines',
            'tutors',
            'filters',
        ]);
    }

    /**
     * Test student cannot access coordinator dashboard.
     */
    public function test_student_cannot_access_coordinator_dashboard(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('progress.coordinator.index'));

        $response->assertStatus(403);
    }

    /**
     * Test guest cannot access any of these dashboards.
     */
    public function test_guest_cannot_access_dashboards(): void
    {
        $response = $this->get(route('progress.student.show', $this->production));
        $response->assertRedirect(route('login'));

        $response2 = $this->get(route('progress.coordinator.index'));
        $response2->assertRedirect(route('login'));
    }

    /**
     * Test coordinator can configure milestones.
     */
    public function test_coordinator_can_configure_milestones(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('progress.milestones.store', $this->production), [
                'milestones' => [
                    [
                        'type' => 'delivery',
                        'title' => 'Entrega 1',
                        'scheduled_date' => '2026-07-01 12:00:00',
                    ],
                    [
                        'type' => 'defense',
                        'title' => 'Defensa Final',
                        'scheduled_date' => '2026-08-01 10:00:00',
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('production_milestones', 2);
        $this->assertDatabaseHas('production_milestones', [
            'production_id' => $this->production->id,
            'title' => 'Entrega 1',
            'type' => 'delivery',
        ]);
    }

    /**
     * Test milestones configuration validation.
     */
    public function test_milestones_configuration_validation(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('progress.milestones.store', $this->production), [
                'milestones' => [
                    [
                        'type' => 'invalid_type', // Validation fails: invalid type
                        'title' => '',             // Validation fails: empty title
                        'scheduled_date' => 'not-a-date', // Validation fails: invalid date
                    ],
                ],
            ]);
        $response->assertSessionHasErrors([
            'milestones.0.type',
            'milestones.0.title',
            'milestones.0.scheduled_date',
        ]);
    }

    /**
     * Test student can view their own progress dashboard when published.
     */
    public function test_student_can_view_own_progress_dashboard_when_published(): void
    {
        $this->production->workflow_state = 'published';
        $this->production->save();

        $response = $this->actingAs($this->student)
            ->get(route('progress.student.show', $this->production));

        $response->assertStatus(200);
    }

    /**
     * Test coordinator cannot view progress dashboard when published.
     */
    public function test_coordinator_cannot_view_progress_dashboard_when_published(): void
    {
        $this->production->workflow_state = 'published';
        $this->production->save();

        $response = $this->actingAs($this->coordinator)
            ->get(route('progress.student.show', $this->production));

        $response->assertStatus(403);
    }

    /**
     * Test tutor cannot view progress dashboard when published.
     */
    public function test_tutor_cannot_view_progress_dashboard_when_published(): void
    {
        $tutor = User::factory()->create();
        $tutor->assignRole('Tutor');
        $this->production->users()->attach($tutor->id, ['role' => 'tutor']);

        $this->production->workflow_state = 'published';
        $this->production->save();

        $response = $this->actingAs($tutor)
            ->get(route('progress.student.show', $this->production));

        $response->assertStatus(403);
    }

    /**
     * Test coordinator cannot configure milestones when published.
     */
    public function test_coordinator_cannot_configure_milestones_when_published(): void
    {
        $this->production->workflow_state = 'published';
        $this->production->save();

        $response = $this->actingAs($this->coordinator)
            ->post(route('progress.milestones.store', $this->production), [
                'milestones' => [
                    [
                        'type' => 'delivery',
                        'title' => 'Hito Posterior',
                        'scheduled_date' => '2026-09-01 12:00:00',
                    ],
                ],
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test coordinator dashboard list excludes published productions.
     */
    public function test_coordinator_dashboard_list_excludes_published_productions(): void
    {
        $publishedProduction = Production::factory()->create(['workflow_state' => 'published']);
        $draftProduction = Production::factory()->create(['workflow_state' => 'draft']);

        $progressService = app(ProgressService::class);
        $result = $progressService->getCoordinatorDashboardData();

        $this->assertTrue($result->getCollection()->contains($draftProduction));
        $this->assertFalse($result->getCollection()->contains($publishedProduction));
    }
}
