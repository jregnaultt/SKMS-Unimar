<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\AuditLog;
use App\Models\ResearchLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Coordinador']);

        // 2. Setup Users
        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');
    }

    // ─── Scenario 1: Authorization/Security ───────────────────────────────────

    public function test_student_cannot_access_any_admin_panel_routes(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.programs.index'))
            ->assertStatus(403);
    }

    public function test_coordinator_can_access_admin_panel_routes(): void
    {
        $this->actingAs($this->coordinator)
            ->get(route('admin.programs.index'))
            ->assertStatus(200);
    }

    // ─── Scenario 2: Academic Program CRUD ───────────────────────────────────

    public function test_coordinator_can_create_academic_program(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.programs.store'), [
                'name' => 'Ingeniería Civil',
                'code' => 'ING-CIV',
                'description' => 'Programa de ingeniería civil en UNIMAR.',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.programs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('academic_programs', [
            'name' => 'Ingeniería Civil',
            'code' => 'ING-CIV',
            'is_active' => 1,
        ]);
    }

    public function test_create_academic_program_validation_errors(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.programs.store'), [
                'name' => '', // Required
                'code' => '', // Required
            ]);

        $response->assertSessionHasErrors(['name', 'code']);
    }

    public function test_coordinator_can_update_academic_program(): void
    {
        $program = AcademicProgram::factory()->create([
            'name' => 'Ingeniería Antigua',
            'code' => 'ING-ANT',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->coordinator)
            ->put(route('admin.programs.update', $program), [
                'name' => 'Ingeniería Moderna',
                'code' => 'ING-MOD',
                'description' => 'Programa actualizado.',
                'is_active' => false,
            ]);

        $response->assertRedirect(route('admin.programs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('academic_programs', [
            'id' => $program->id,
            'name' => 'Ingeniería Moderna',
            'code' => 'ING-MOD',
            'is_active' => 0,
        ]);
    }

    public function test_coordinator_can_delete_academic_program(): void
    {
        $program = AcademicProgram::factory()->create();

        $response = $this->actingAs($this->coordinator)
            ->delete(route('admin.programs.destroy', $program));

        $response->assertRedirect(route('admin.programs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('academic_programs', [
            'id' => $program->id,
        ]);
    }

    // ─── Scenario 3: Research Line CRUD ──────────────────────────────────────

    public function test_coordinator_can_create_research_line(): void
    {
        $program = AcademicProgram::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.lines.store'), [
                'name' => 'Inteligencia Artificial',
                'academic_program_id' => $program->id,
                'description' => 'Línea de IA y Redes Neuronales.',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.lines.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('research_lines', [
            'name' => 'Inteligencia Artificial',
            'academic_program_id' => $program->id,
            'is_active' => 1,
        ]);
    }

    public function test_create_research_line_validation_errors(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.lines.store'), [
                'name' => '', // Required
                'academic_program_id' => 999999, // Invalid
            ]);

        $response->assertSessionHasErrors(['name', 'academic_program_id']);
    }

    public function test_coordinator_can_update_research_line(): void
    {
        $program1 = AcademicProgram::factory()->create();
        $program2 = AcademicProgram::factory()->create();

        $line = ResearchLine::factory()->create([
            'name' => 'Línea Antigua',
            'academic_program_id' => $program1->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->coordinator)
            ->put(route('admin.lines.update', $line), [
                'name' => 'Línea Moderna',
                'academic_program_id' => $program2->id,
                'description' => 'Descripción actualizada.',
                'is_active' => false,
            ]);

        $response->assertRedirect(route('admin.lines.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('research_lines', [
            'id' => $line->id,
            'name' => 'Línea Moderna',
            'academic_program_id' => $program2->id,
            'is_active' => 0,
        ]);
    }

    public function test_coordinator_can_delete_research_line(): void
    {
        $line = ResearchLine::factory()->create();

        $response = $this->actingAs($this->coordinator)
            ->delete(route('admin.lines.destroy', $line));

        $response->assertRedirect(route('admin.lines.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('research_lines', [
            'id' => $line->id,
        ]);
    }

    // ─── Scenario 4: Academic Period CRUD ────────────────────────────────────

    public function test_coordinator_can_create_academic_period(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.periods.store'), [
                'name' => '2026-I',
                'start_date' => '2026-01-15',
                'end_date' => '2026-04-15',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.periods.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('academic_periods', [
            'name' => '2026-I',
            'is_active' => 1,
        ]);
    }

    public function test_create_academic_period_validation_date_rules(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.periods.store'), [
                'name' => '2026-II',
                'start_date' => '2026-05-15',
                'end_date' => '2026-04-15', // Invalid: before start_date
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors(['end_date']);
    }

    public function test_coordinator_can_update_academic_period(): void
    {
        $period = AcademicPeriod::factory()->create([
            'name' => '2025-II',
            'start_date' => '2025-09-01',
            'end_date' => '2025-12-15',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->coordinator)
            ->put(route('admin.periods.update', $period), [
                'name' => '2025-II Modificado',
                'start_date' => '2025-09-01',
                'end_date' => '2025-12-20',
                'is_active' => false,
            ]);

        $response->assertRedirect(route('admin.periods.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('academic_periods', [
            'id' => $period->id,
            'name' => '2025-II Modificado',
            'is_active' => 0,
        ]);
    }

    public function test_coordinator_can_delete_academic_period(): void
    {
        $period = AcademicPeriod::factory()->create();

        $response = $this->actingAs($this->coordinator)
            ->delete(route('admin.periods.destroy', $period));

        $response->assertRedirect(route('admin.periods.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('academic_periods', [
            'id' => $period->id,
        ]);
    }

    // ─── Scenario 5: User Roles Management ───────────────────────────────────

    public function test_coordinator_can_view_users_list(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewHas('users');
    }

    public function test_coordinator_can_assign_role_to_user(): void
    {
        Role::firstOrCreate(['name' => 'Tutor']);
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $response = $this->actingAs($this->coordinator)
            ->put(route('admin.users.update', $user), [
                'roles' => ['Tutor'],
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertTrue($user->fresh()->hasRole('Tutor'));
        $this->assertFalse($user->fresh()->hasRole('Estudiante'));
    }

    public function test_assigning_invalid_role_triggers_validation_errors(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->coordinator)
            ->put(route('admin.users.update', $user), [
                'roles' => ['RoleInexistente'],
            ]);

        $response->assertSessionHasErrors(['roles.0']);
    }

    // ─── Scenario 6: Audit Logs Management ───────────────────────────────────

    public function test_coordinator_can_view_audit_logs(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->coordinator->id,
            'action' => 'test_action',
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $this->coordinator->id,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->coordinator)
            ->get(route('admin.audit-logs.index'));

        $response->assertStatus(200);
        $response->assertViewHas('logs');
    }

    public function test_coordinator_can_inspect_single_audit_log(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->coordinator->id,
            'action' => 'test_action',
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $this->coordinator->id,
            'ip_address' => '127.0.0.1',
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
        ]);

        $response = $this->actingAs($this->coordinator)
            ->get(route('admin.audit-logs.show', $log));

        $response->assertStatus(200);
        $response->assertJsonPath('action', 'test_action');
        $response->assertJsonPath('old_values.name', 'Old Name');
        $response->assertJsonPath('new_values.name', 'New Name');
    }
}
