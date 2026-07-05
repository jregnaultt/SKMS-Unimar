<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignedProductionsTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $tutor;

    private User $jury;

    private User $coordinator;

    private User $decano;

    private AcademicPeriod $period;

    private AcademicProgram $program;

    private ResearchLine $line;

    private ProductionType $type;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Spatie Roles
        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Tutor']);
        Role::firstOrCreate(['name' => 'Jurado']);
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Decano']);

        // 2. Create academic structures
        $this->period = AcademicPeriod::factory()->create(['is_active' => true]);
        $this->program = AcademicProgram::factory()->create(['is_active' => true]);
        $this->line = ResearchLine::factory()->create(['academic_program_id' => $this->program->id, 'is_active' => true]);
        $this->type = ProductionType::factory()->create();

        // 3. Create Users and assign roles
        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        $this->tutor = User::factory()->create();
        $this->tutor->assignRole('Tutor');

        $this->jury = User::factory()->create();
        $this->jury->assignRole('Jurado');

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');

        $this->decano = User::factory()->create();
        $this->decano->assignRole('Decano');
    }

    /**
     * Test guest is redirected to login.
     */
    public function test_guest_cannot_access_assigned_productions(): void
    {
        $response = $this->get(route('assigned-productions.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test student receives forbidden error.
     */
    public function test_student_cannot_access_assigned_productions(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('assigned-productions.index'));

        $response->assertStatus(403);
    }

    /**
     * Test tutor can access and see only their own tutor assignments.
     */
    public function test_tutor_can_access_and_see_their_own_assignments(): void
    {
        // Production 1: Assigned to tutor
        $prod1 = Production::factory()->create([
            'academic_period_id' => $this->period->id,
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
        ]);
        $prod1->users()->attach($this->tutor->id, ['role' => 'tutor']);

        // Production 2: Assigned to another tutor
        $otherTutor = User::factory()->create();
        $otherTutor->assignRole('Tutor');
        $prod2 = Production::factory()->create([
            'academic_period_id' => $this->period->id,
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
        ]);
        $prod2->users()->attach($otherTutor->id, ['role' => 'tutor']);

        $response = $this->actingAs($this->tutor)
            ->get(route('assigned-productions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('assigned-productions.index');

        $tutorProductions = $response->viewData('tutorProductions');
        $this->assertCount(1, $tutorProductions);
        $this->assertEquals($prod1->id, $tutorProductions->first()->id);
    }

    /**
     * Test jury can access and see only their own jury assignments.
     */
    public function test_jury_can_access_and_see_their_own_assignments(): void
    {
        $prod1 = Production::factory()->create([
            'academic_period_id' => $this->period->id,
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
        ]);
        $prod1->users()->attach($this->jury->id, ['role' => 'jury']);

        $response = $this->actingAs($this->jury)
            ->get(route('assigned-productions.index'));

        $response->assertStatus(200);

        $juryProductions = $response->viewData('juryProductions');
        $this->assertCount(1, $juryProductions);
        $this->assertEquals($prod1->id, $juryProductions->first()->id);
    }

    /**
     * Test coordinator and decano can see global assigned productions list.
     */
    public function test_coordinator_and_decano_can_see_global_assignments(): void
    {
        // Tesis 1: Tutor y Jurado asignados
        $prod = Production::factory()->create([
            'academic_period_id' => $this->period->id,
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
        ]);
        $prod->users()->attach($this->tutor->id, ['role' => 'tutor']);
        $prod->users()->attach($this->jury->id, ['role' => 'jury']);

        // Test Coordinator
        $response = $this->actingAs($this->coordinator)
            ->get(route('assigned-productions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('assigned-productions.index');

        $tutors = $response->viewData('tutors');
        $jurados = $response->viewData('jurados');

        $this->assertGreaterThanOrEqual(1, $tutors->count());
        $this->assertGreaterThanOrEqual(1, $jurados->count());

        // Test Decano
        $decanoResponse = $this->actingAs($this->decano)
            ->get(route('assigned-productions.index'));

        $decanoResponse->assertStatus(200);
    }
}
