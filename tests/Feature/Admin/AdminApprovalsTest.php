<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminApprovalsTest extends TestCase
{
    use RefreshDatabase;

    protected User $coordinator;

    protected User $tutor;

    protected User $student;

    protected Subject $seminario;

    protected Subject $trabajoI;

    protected Production $production;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Tutor']);
        Role::firstOrCreate(['name' => 'Estudiante']);

        // 2. Setup Users
        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');

        $this->tutor = User::factory()->create();
        $this->tutor->assignRole('Tutor');

        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        // 3. Setup Subjects
        $this->seminario = Subject::create(['name' => 'Seminario Metodológico', 'code' => 'SMI1004341']);
        $this->trabajoI = Subject::create(['name' => 'Trabajo de Investigación I', 'code' => 'TRI1106341']);

        // 4. Setup Metadata
        $program = AcademicProgram::create(['name' => 'Sistemas', 'code' => 'SIS', 'is_active' => true]);
        $line = ResearchLine::create(['academic_program_id' => $program->id, 'name' => 'IA', 'is_active' => true]);
        $type = ProductionType::create(['name' => 'Tesis']);
        $period = AcademicPeriod::create(['name' => '2026-I', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'is_active' => true]);

        // 5. Setup Production for Seminario
        $this->production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de Seminario',
            'abstract' => 'Resumen Seminario',
            'academic_program_id' => $program->id,
            'research_line_id' => $line->id,
            'production_type_id' => $type->id,
            'academic_period_id' => $period->id,
            'subject_id' => $this->seminario->id,
            'workflow_state' => 'under_tutor_review',
        ]);

        $this->production->users()->attach($this->student->id, ['role' => 'author']);
        $this->production->users()->attach($this->tutor->id, ['role' => 'tutor']);
    }

    public function test_tutor_can_transition_seminario_to_coordinator_review(): void
    {
        $response = $this->actingAs($this->tutor)->post(route('productions.transition', $this->production), [
            'target_state' => 'under_coordinator_review',
        ]);

        $response->assertRedirect();
        $this->production->refresh();
        $this->assertEquals('under_coordinator_review', $this->production->workflow_state);
    }

    public function test_coordinator_can_view_approvals_list(): void
    {
        $this->production->update(['workflow_state' => 'under_coordinator_review']);

        $response = $this->actingAs($this->coordinator)->get(route('admin.approvals.index'));
        $response->assertStatus(200);
        $response->assertSee('Tesis de Seminario');
    }

    public function test_coordinator_can_approve_seminario(): void
    {
        $this->production->update(['workflow_state' => 'under_coordinator_review']);

        $response = $this->actingAs($this->coordinator)->post(route('productions.transition', $this->production), [
            'target_state' => 'approved',
        ]);

        $response->assertRedirect();
        $this->production->refresh();
        $this->assertEquals('approved', $this->production->workflow_state);

        // Student should receive notification and see transition banner on dashboard
        $dbNotification = $this->student->notifications()->first();
        $this->assertNotNull($dbNotification);
        $this->assertStringContainsString('Ya puedes inscribirte en Trabajo de Investigación I', $dbNotification->data['message']);

        // Check student dashboard data contains showTransitionToTrabajoI
        $dashResponse = $this->actingAs($this->student)->get(route('dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Comenzar Trabajo I');
    }
}
