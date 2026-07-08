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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminJuryAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $coordinator;

    protected User $student;

    protected User $jury;

    protected Subject $trabajoII;

    protected Subject $trabajoI;

    protected Production $productionII;

    protected Production $productionI;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Jurado']);
        Role::firstOrCreate(['name' => 'Tutor']);

        // 2. Setup Users
        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');

        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        $this->jury = User::factory()->create();
        $this->jury->assignRole('Jurado');

        // 3. Setup Subjects
        $this->trabajoI = Subject::create(['name' => 'Trabajo de Investigación I', 'code' => 'TRI1106341']);
        $this->trabajoII = Subject::create(['name' => 'Trabajo de Investigación II', 'code' => 'TRI1206441']);

        // 4. Setup metadata
        $program = AcademicProgram::create(['name' => 'Sistemas', 'code' => 'SIS', 'is_active' => true]);
        $line = ResearchLine::create(['academic_program_id' => $program->id, 'name' => 'IA', 'is_active' => true]);
        $type = ProductionType::create(['name' => 'Tesis']);
        $period = AcademicPeriod::create(['name' => '2026-I', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'is_active' => true]);

        // 5. Setup Productions
        $this->productionI = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Proyecto Primero de Grado',
            'abstract' => 'Resumen I',
            'academic_program_id' => $program->id,
            'research_line_id' => $line->id,
            'production_type_id' => $type->id,
            'academic_period_id' => $period->id,
            'subject_id' => $this->trabajoI->id,
            'workflow_state' => 'approved',
        ]);

        $this->productionII = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis Final de Sistemas',
            'abstract' => 'Resumen II',
            'academic_program_id' => $program->id,
            'research_line_id' => $line->id,
            'production_type_id' => $type->id,
            'academic_period_id' => $period->id,
            'subject_id' => $this->trabajoII->id,
            'workflow_state' => 'draft',
        ]);
    }

    public function test_non_coordinator_cannot_access_jury_assignment_page(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.juries.index'));
        $response->assertStatus(403);
    }

    public function test_coordinator_can_access_jury_assignment_page_and_sees_only_trabajo_ii(): void
    {
        $response = $this->actingAs($this->coordinator)->get(route('admin.juries.index'));
        $response->assertStatus(200);
        $response->assertSee('Tesis Final de Sistemas');
        $response->assertDontSee('Proyecto Primero de Grado');
    }

    public function test_coordinator_can_assign_juries_to_trabajo_ii(): void
    {
        $jury2 = User::factory()->create();
        $jury2->assignRole('Jurado');

        $response = $this->actingAs($this->coordinator)->post(route('admin.juries.assign', $this->productionII), [
            'jury_1_id' => $this->jury->id,
            'jury_2_id' => $jury2->id,
        ]);

        $response->assertRedirect(route('admin.juries.index'));
        $response->assertSessionHas('success');

        $this->assertTrue(
            $this->productionII->users()
                ->where('user_id', $this->jury->id)
                ->wherePivot('role', 'jury')
                ->exists()
        );

        $this->assertTrue(
            $this->productionII->users()
                ->where('user_id', $jury2->id)
                ->wherePivot('role', 'jury')
                ->exists()
        );
    }

    public function test_juries_are_preassigned_on_trabajo_i_approval_and_copied_to_trabajo_ii_on_creation(): void
    {
        $jury2 = User::factory()->create();
        $jury2->assignRole('Jurado');

        // 1. Move Trabajo I to under_coordinator_review so it can be transitioned to approved by coordinator
        $this->productionI->users()->attach($this->student->id, ['role' => 'author']);
        $this->productionI->update(['workflow_state' => 'under_coordinator_review']);

        // 2. Approve Trabajo I and pass preassigned juries
        $response = $this->actingAs($this->coordinator)->post(route('productions.transition', $this->productionI), [
            'target_state' => 'approved',
            'preassigned_jury_1_id' => $this->jury->id,
            'preassigned_jury_2_id' => $jury2->id,
        ]);

        $response->assertRedirect();
        $this->productionI->refresh();

        $this->assertEquals($this->jury->id, $this->productionI->preassigned_jury_1_id);
        $this->assertEquals($jury2->id, $this->productionI->preassigned_jury_2_id);

        // 3. Now simulate the student creating Trabajo II
        // First create tutor user
        $tutor = User::factory()->create();
        $tutor->assignRole('Tutor');

        $fileId = (string) Str::uuid();
        Storage::disk('local')->put("temp_pdfs/{$fileId}.pdf", 'Dummy PDF Content');

        $response = $this->actingAs($this->student)->post(route('productions.store'), [
            'title' => 'Tesis de Trabajo II Nueva',
            'abstract' => 'Nuevo Resumen',
            'authors' => 'Autor Alumno',
            'keywords' => 'test, laravel',
            'tutor_id' => $tutor->id,
            'academic_program_id' => $this->productionI->academic_program_id,
            'research_line_id' => $this->productionI->research_line_id,
            'production_type_id' => $this->productionI->production_type_id,
            'academic_period_id' => $this->productionI->academic_period_id,
            'subject_id' => $this->trabajoII->id,
            'file_id' => $fileId,
            'action' => 'draft',
        ]);

        $response->assertRedirect();

        // Retrieve the newly created Trabajo II production
        $newProdII = Production::where('subject_id', $this->trabajoII->id)
            ->whereHas('users', function ($q) {
                $q->where('users.id', $this->student->id)->where('role', 'author');
            })
            ->orderBy('created_at', 'desc')
            ->first();

        $this->assertNotNull($newProdII);

        // Verify the two preassigned juries were automatically copied
        $this->assertTrue(
            $newProdII->users()
                ->where('user_id', $this->jury->id)
                ->wherePivot('role', 'jury')
                ->exists()
        );

        $this->assertTrue(
            $newProdII->users()
                ->where('user_id', $jury2->id)
                ->wherePivot('role', 'jury')
                ->exists()
        );
    }
}
