<?php

namespace Tests\Unit;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\DocumentVersion;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowService $workflowService;

    protected User $student;

    protected User $tutorUser;

    protected User $juryUser;

    protected User $coordinatorUser;

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

        $this->tutorUser = User::factory()->create();
        $this->tutorUser->assignRole('Tutor');

        $this->juryUser = User::factory()->create();
        $this->juryUser->assignRole('Jurado');

        $this->coordinatorUser = User::factory()->create();
        $this->coordinatorUser->assignRole('Coordinador');

        // 3. Setup Catalogs
        $program = AcademicProgram::create(['name' => 'Sistemas', 'code' => 'SIS', 'is_active' => true]);
        $line = ResearchLine::create(['academic_program_id' => $program->id, 'name' => 'IA', 'is_active' => true]);
        $type = ProductionType::create(['name' => 'Tesis']);
        $period = AcademicPeriod::create(['name' => '2026-I', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'is_active' => true]);

        // 4. Setup Production
        $this->production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de Prueba',
            'abstract' => 'Resumen de prueba.',
            'academic_program_id' => $program->id,
            'research_line_id' => $line->id,
            'production_type_id' => $type->id,
            'academic_period_id' => $period->id,
            'workflow_state' => 'draft',
        ]);

        // Attach student as author
        $this->production->users()->attach($this->student->id, ['role' => 'author']);

        // Instantiate service
        $this->workflowService = new WorkflowService;

        Storage::fake('local');
    }

    public function test_student_can_transition_from_draft_to_under_review(): void
    {
        // Setup initial media for version 1
        $this->production->addMediaFromString('Dummy PDF content')
            ->toMediaCollection('documento');

        $this->assertTrue($this->workflowService->canTransition($this->production, 'under_review', $this->student));

        $this->workflowService->transition($this->production, 'under_review', $this->student);

        $this->assertEquals('under_review', $this->production->refresh()->workflow_state);
        $this->assertNotNull($this->production->submission_date);

        // Verify version 1 is created
        $this->assertDatabaseHas('document_versions', [
            'production_id' => $this->production->id,
            'version_number' => 1,
            'user_id' => $this->student->id,
        ]);

        // Verify revision history is recorded
        $this->assertDatabaseHas('revisions', [
            'production_id' => $this->production->id,
            'user_id' => $this->student->id,
            'previous_state' => 'draft',
            'new_state' => 'under_review',
        ]);
    }

    public function test_student_cannot_transition_to_invalid_states(): void
    {
        $this->assertFalse($this->workflowService->canTransition($this->production, 'approved', $this->student));
        $this->assertFalse($this->workflowService->canTransition($this->production, 'needs_corrections', $this->student));
        $this->assertFalse($this->workflowService->canTransition($this->production, 'rejected', $this->student));

        $this->expectException(\InvalidArgumentException::class);
        $this->workflowService->transition($this->production, 'approved', $this->student);
    }

    public function test_tutor_or_jury_can_transition_under_review_states(): void
    {
        // Set production state to under_review
        $this->production->update(['workflow_state' => 'under_review']);

        // Attach tutor and jury
        $this->production->users()->attach($this->tutorUser->id, ['role' => 'tutor']);
        $this->production->users()->attach($this->juryUser->id, ['role' => 'jury']);

        // Tutor checks
        $this->assertTrue($this->workflowService->canTransition($this->production, 'needs_corrections', $this->tutorUser));
        $this->assertTrue($this->workflowService->canTransition($this->production, 'approved', $this->tutorUser));
        $this->assertTrue($this->workflowService->canTransition($this->production, 'rejected', $this->tutorUser));

        // Jury checks
        $this->assertTrue($this->workflowService->canTransition($this->production, 'needs_corrections', $this->juryUser));
        $this->assertTrue($this->workflowService->canTransition($this->production, 'approved', $this->juryUser));
        $this->assertTrue($this->workflowService->canTransition($this->production, 'rejected', $this->juryUser));

        // Execute transition to approved by tutor
        $this->workflowService->transition($this->production, 'approved', $this->tutorUser);
        $this->assertEquals('approved', $this->production->refresh()->workflow_state);
        $this->assertNotNull($this->production->approval_date);
    }

    public function test_unassigned_tutor_or_jury_cannot_transition(): void
    {
        $this->production->update(['workflow_state' => 'under_review']);

        // Do not attach the tutor/jury to the production
        $this->assertFalse($this->workflowService->canTransition($this->production, 'approved', $this->tutorUser));
        $this->assertFalse($this->workflowService->canTransition($this->production, 'approved', $this->juryUser));
    }

    public function test_coordinator_can_override_and_transition(): void
    {
        $this->production->update(['workflow_state' => 'under_review']);

        // Coordinator does not need to be attached in pivot
        $this->assertTrue($this->workflowService->canTransition($this->production, 'approved', $this->coordinatorUser));

        $this->workflowService->transition($this->production, 'approved', $this->coordinatorUser);
        $this->assertEquals('approved', $this->production->refresh()->workflow_state);
    }

    public function test_coordinator_can_publish_approved_production(): void
    {
        $this->production->update(['workflow_state' => 'approved']);

        $this->assertTrue($this->workflowService->canTransition($this->production, 'published', $this->coordinatorUser));
        $this->assertFalse($this->workflowService->canTransition($this->production, 'published', $this->student));

        $this->workflowService->transition($this->production, 'published', $this->coordinatorUser);
        $this->assertEquals('published', $this->production->refresh()->workflow_state);
    }

    public function test_student_can_resubmit_with_corrections_creating_version_two(): void
    {
        // 1. Setup production in needs_corrections state with initial version 1
        $this->production->update(['workflow_state' => 'needs_corrections']);
        $this->production->addMediaFromString('Version 1 Content')
            ->toMediaCollection('documento');

        DocumentVersion::create([
            'production_id' => $this->production->id,
            'version_number' => 1,
            'user_id' => $this->student->id,
            'changelog' => 'Carga inicial',
        ]);

        // 2. Put a mock file in local storage temp_pdfs
        $fileId = (string) Str::uuid();
        Storage::disk('local')->put("temp_pdfs/{$fileId}.pdf", 'Version 2 Content');

        // 3. Perform transition
        $this->assertTrue($this->workflowService->canTransition($this->production, 'under_review', $this->student));

        $this->workflowService->transition($this->production, 'under_review', $this->student, [
            'file_id' => $fileId,
            'changelog' => 'Se corrigieron los parrafos sugeridos por el tutor.',
        ]);

        // 4. Assertions
        $this->production->refresh();
        $this->assertEquals('under_review', $this->production->workflow_state);

        // Verify version 2 exists
        $this->assertDatabaseHas('document_versions', [
            'production_id' => $this->production->id,
            'version_number' => 2,
            'user_id' => $this->student->id,
            'changelog' => 'Se corrigieron los parrafos sugeridos por el tutor.',
        ]);

        $version2 = DocumentVersion::where('production_id', $this->production->id)
            ->where('version_number', 2)
            ->first();

        // Verify new file is attached to version 2
        $this->assertTrue($version2->hasMedia('documento_version'));

        // Verify main production has updated file
        $this->assertTrue($this->production->hasMedia('documento'));

        // Verify temp file is cleaned up
        Storage::disk('local')->assertMissing("temp_pdfs/{$fileId}.pdf");
    }
}
