<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\DocumentVersion;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected User $tutor;

    protected User $coordinator;

    protected Production $production;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Tutor']);
        Role::firstOrCreate(['name' => 'Coordinador']);

        // 2. Setup Users
        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        $this->tutor = User::factory()->create();
        $this->tutor->assignRole('Tutor');

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');

        // 3. Setup Catalogs
        $program = AcademicProgram::create(['name' => 'Sistemas', 'code' => 'SIS', 'is_active' => true]);
        $line = ResearchLine::create(['academic_program_id' => $program->id, 'name' => 'IA', 'is_active' => true]);
        $type = ProductionType::create(['name' => 'Tesis']);
        $period = AcademicPeriod::create(['name' => '2026-I', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'is_active' => true]);

        // 4. Setup Production as Draft
        $this->production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de Flujo Completo',
            'abstract' => 'Este es el resumen de la tesis para flujo completo.',
            'academic_program_id' => $program->id,
            'research_line_id' => $line->id,
            'production_type_id' => $type->id,
            'academic_period_id' => $period->id,
            'workflow_state' => 'draft',
        ]);

        // Attach student as author, and tutor as tutor
        $this->production->users()->attach($this->student->id, ['role' => 'author']);
        $this->production->users()->attach($this->tutor->id, ['role' => 'tutor']);

        Storage::fake('local');
    }

    public function test_full_workflow_lifecycle_integration(): void
    {
        // --- STEP 1: Student submits draft for review ---
        $this->production->addMediaFromString('Initial Draft PDF')
            ->toMediaCollection('documento');

        $response = $this->actingAs($this->student)->post(route('productions.transition', $this->production), [
            'target_state' => 'under_review',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '¡El documento ha sido enviado a revisión exitosamente!');

        $this->assertEquals('under_review', $this->production->refresh()->workflow_state);

        // Verify version 1 is created
        $this->assertDatabaseHas('document_versions', [
            'production_id' => $this->production->id,
            'version_number' => 1,
        ]);

        // Verify revision is logged
        $this->assertDatabaseHas('revisions', [
            'production_id' => $this->production->id,
            'new_state' => 'under_review',
        ]);

        // Verify tutor was notified in DB notifications
        $this->assertEquals(1, $this->tutor->notifications()->count());
        $notificationData = $this->tutor->notifications()->first()->data;
        $this->assertEquals('Nueva producción científica por revisar', $notificationData['title']);

        // --- STEP 2: Tutor reviews and requests corrections ---
        $response = $this->actingAs($this->tutor)->post(route('productions.transition', $this->production), [
            'target_state' => 'needs_corrections',
            'comment' => 'Por favor corrige la introducción y el marco teórico.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Se ha solicitado la corrección del documento con éxito.');

        $this->assertEquals('needs_corrections', $this->production->refresh()->workflow_state);

        // Verify student was notified
        $this->assertEquals(1, $this->student->notifications()->count());
        $studentNotification = $this->student->notifications()->first()->data;
        $this->assertEquals('Se requieren correcciones', $studentNotification['title']);
        $this->assertEquals('Por favor corrige la introducción y el marco teórico.', $studentNotification['comment']);

        // --- STEP 3: Student resubmits with corrections ---
        $fileId = (string) Str::uuid();
        Storage::disk('local')->put("temp_pdfs/{$fileId}.pdf", 'Corrected Version PDF');

        $response = $this->actingAs($this->student)->post(route('productions.transition', $this->production), [
            'target_state' => 'under_review',
            'file_id' => $fileId,
            'changelog' => 'Se corrigió la introducción y marco teórico.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '¡El documento ha sido enviado a revisión exitosamente!');

        $this->production->refresh();
        $this->assertEquals('under_review', $this->production->workflow_state);

        // Verify version 2 exists in DB
        $this->assertDatabaseHas('document_versions', [
            'production_id' => $this->production->id,
            'version_number' => 2,
            'changelog' => 'Se corrigió la introducción y marco teórico.',
        ]);

        $version2 = DocumentVersion::where('production_id', $this->production->id)
            ->where('version_number', 2)
            ->first();

        // Verify version 2 has media associated
        $this->assertTrue($version2->hasMedia('documento_version'));

        // --- STEP 4: Tutor approves the production ---
        $response = $this->actingAs($this->tutor)->post(route('productions.transition', $this->production), [
            'target_state' => 'approved',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '¡Producción científica aprobada con éxito!');
        $this->assertEquals('approved', $this->production->refresh()->workflow_state);
        $this->assertNotNull($this->production->approval_date);

        // --- STEP 5: Coordinator publishes the production ---
        $response = $this->actingAs($this->coordinator)->post(route('productions.transition', $this->production), [
            'target_state' => 'published',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '¡Producción científica publicada exitosamente en el repositorio!');
        $this->assertEquals('published', $this->production->refresh()->workflow_state);
    }
}
