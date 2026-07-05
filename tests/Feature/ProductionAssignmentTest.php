<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected User $tutor;

    protected User $jury;

    protected User $coordinator;

    protected Production $production;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Tutor']);
        Role::firstOrCreate(['name' => 'Jurado']);
        Role::firstOrCreate(['name' => 'Coordinador']);

        // 2. Setup Users
        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        $this->tutor = User::factory()->create();
        $this->tutor->assignRole('Tutor');

        $this->jury = User::factory()->create();
        $this->jury->assignRole('Jurado');

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');

        // 3. Setup Catalogs
        $program = AcademicProgram::create(['name' => 'Sistemas', 'code' => 'SIS', 'is_active' => true]);
        $line = ResearchLine::create(['academic_program_id' => $program->id, 'name' => 'IA', 'is_active' => true]);
        $type = ProductionType::create(['name' => 'Tesis']);
        $period = AcademicPeriod::create(['name' => '2026-I', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'is_active' => true]);

        // 4. Setup Production
        $this->production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de Asignación',
            'abstract' => 'Resumen de la tesis.',
            'academic_program_id' => $program->id,
            'research_line_id' => $line->id,
            'production_type_id' => $type->id,
            'academic_period_id' => $period->id,
            'workflow_state' => 'draft',
        ]);

        $this->production->users()->attach($this->student->id, ['role' => 'author']);
    }

    public function test_student_cannot_assign_tutor_or_jury(): void
    {
        $response = $this->actingAs($this->student)->post(route('productions.assign-users', $this->production), [
            'tutor_id' => $this->tutor->id,
            'jury_id' => $this->jury->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_coordinator_can_assign_tutor_and_jury(): void
    {
        $response = $this->actingAs($this->coordinator)->post(route('productions.assign-users', $this->production), [
            'tutor_id' => $this->tutor->id,
            'jury_id' => $this->jury->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Asignación de tutor y jurado guardada con éxito.');

        $this->assertDatabaseHas('production_user', [
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
            'role' => 'tutor',
        ]);

        $this->assertDatabaseHas('production_user', [
            'production_id' => $this->production->id,
            'user_id' => $this->jury->id,
            'role' => 'jury',
        ]);
    }

    public function test_coordinator_can_remove_tutor_and_jury(): void
    {
        // First assign
        $this->production->users()->attach($this->tutor->id, ['role' => 'tutor']);
        $this->production->users()->attach($this->jury->id, ['role' => 'jury']);

        // Now detach via assign-users POST with empty fields
        $response = $this->actingAs($this->coordinator)->post(route('productions.assign-users', $this->production), [
            'tutor_id' => null,
            'jury_id' => null,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseMissing('production_user', [
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
            'role' => 'tutor',
        ]);

        $this->assertDatabaseMissing('production_user', [
            'production_id' => $this->production->id,
            'user_id' => $this->jury->id,
            'role' => 'jury',
        ]);

        // Student (author) is still attached!
        $this->assertDatabaseHas('production_user', [
            'production_id' => $this->production->id,
            'user_id' => $this->student->id,
            'role' => 'author',
        ]);
    }
}
