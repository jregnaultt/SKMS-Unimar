<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionUploadTest extends TestCase
{
    use RefreshDatabase;

    protected $student;

    protected $tutor;

    protected $program;

    protected $line;

    protected $type;

    protected $period;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base roles
        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Tutor']);

        // Create standard test student
        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        // Create standard test tutor
        $this->tutor = User::factory()->create();
        $this->tutor->assignRole('Tutor');

        // Create standard catalogs
        $this->program = AcademicProgram::create([
            'name' => 'Ingenieria de Sistemas',
            'code' => 'ING-SIS',
            'is_active' => true,
        ]);

        $this->line = ResearchLine::create([
            'academic_program_id' => $this->program->id,
            'name' => 'Inteligencia Artificial',
            'is_active' => true,
        ]);

        $this->type = ProductionType::create([
            'name' => 'Tesis de Grado',
            'description' => 'Trabajo especial de grado para optar al titulo',
        ]);

        $this->period = AcademicPeriod::create([
            'name' => '2026-I',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        Storage::fake('local');
    }

    public function test_upload_requires_valid_metadata_fields(): void
    {
        $response = $this->actingAs($this->student)->post(route('productions.store'), []);

        $response->assertSessionHasErrors([
            'title', 'abstract', 'authors', 'tutor_id', 'keywords',
            'academic_program_id', 'research_line_id',
            'production_type_id', 'academic_period_id',
            'file_id', 'action',
        ]);
    }

    public function test_user_can_save_production_as_draft(): void
    {
        $fileId = (string) Str::uuid();
        Storage::disk('local')->put("temp_pdfs/{$fileId}.pdf", 'Dummy PDF Content');

        $payload = [
            'title' => 'Una Tesis Sorprendente de IA',
            'abstract' => 'Este es el resumen de la tesis de inteligencia artificial.',
            'authors' => 'Javier Andres Regnault',
            'tutor_id' => $this->tutor->id,
            'keywords' => 'IA, Redes Neuronales, Laravel',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'file_id' => $fileId,
            'action' => 'draft',
        ];

        $response = $this->actingAs($this->student)->post(route('productions.store'), $payload);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', '¡Producción científica guardada como borrador con éxito!');

        $this->assertDatabaseHas('productions', [
            'title' => 'Una Tesis Sorprendente de IA',
            'workflow_state' => 'draft',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'submission_date' => null,
        ]);

        $production = Production::where('title', 'Una Tesis Sorprendente de IA')->first();

        // Assert pivot relation was created
        $this->assertDatabaseHas('production_user', [
            'production_id' => $production->id,
            'user_id' => $this->student->id,
            'role' => 'author',
        ]);

        // Assert keywords were synchronized
        $this->assertCount(3, $production->keywords);
        $this->assertTrue($production->keywords->contains('name', 'IA'));

        // Assert Spatie media document was associated
        $this->assertTrue($production->hasMedia('documento'));

        // Assert temporary file was cleaned up
        Storage::disk('local')->assertMissing("temp_pdfs/{$fileId}.pdf");
    }

    public function test_user_can_save_and_submit_production_for_review(): void
    {
        $fileId = (string) Str::uuid();
        Storage::disk('local')->put("temp_pdfs/{$fileId}.docx", 'Dummy DOCX Content');

        $payload = [
            'title' => 'Analisis de Sistemas Inteligentes',
            'abstract' => 'Este es el resumen de la tesis de sistemas inteligentes.',
            'authors' => 'Javier Andres Regnault',
            'tutor_id' => $this->tutor->id,
            'keywords' => 'Sistemas, IA',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'file_id' => $fileId,
            'action' => 'submit',
        ];

        $response = $this->actingAs($this->student)->post(route('productions.store'), $payload);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', '¡Producción científica guardada y enviada a revisión con éxito!');

        $this->assertDatabaseHas('productions', [
            'title' => 'Analisis de Sistemas Inteligentes',
            'workflow_state' => 'under_tutor_review',
        ]);

        $production = Production::where('title', 'Analisis de Sistemas Inteligentes')->first();
        $this->assertNotNull($production->submission_date);

        // Assert Spatie media document was associated
        $this->assertTrue($production->hasMedia('documento'));

        // Assert temporary file was cleaned up
        Storage::disk('local')->assertMissing("temp_pdfs/{$fileId}.docx");
    }

    public function test_fails_if_temporary_file_does_not_exist(): void
    {
        $payload = [
            'title' => 'Una Tesis Fantasma',
            'abstract' => 'Este es el resumen de la tesis fantasma.',
            'authors' => 'Javier Andres Regnault',
            'tutor_id' => $this->tutor->id,
            'keywords' => 'IA',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'file_id' => 'non-existent-file-id',
            'action' => 'draft',
        ];

        $response = $this->actingAs($this->student)->post(route('productions.store'), $payload);

        $response->assertSessionHas('error', 'El archivo subido no se encuentra en el servidor o ha expirado. Por favor, sube el documento de nuevo.');
        $this->assertDatabaseMissing('productions', [
            'title' => 'Una Tesis Fantasma',
        ]);
    }

    public function test_user_can_submit_draft(): void
    {
        $prod = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Mi Borrador Tesis',
            'workflow_state' => 'draft',
        ]);
        $prod->users()->attach($this->student->id, ['role' => 'author']);

        $response = $this->actingAs($this->student)->post(route('productions.submit-draft', $prod));

        $response->assertRedirect();
        $response->assertSessionHas('success', '¡El borrador ha sido enviado a revisión con éxito!');

        $this->assertDatabaseHas('productions', [
            'id' => $prod->id,
            'workflow_state' => 'under_tutor_review',
        ]);
        $this->assertNotNull($prod->refresh()->submission_date);
    }

    public function test_user_can_delete_draft(): void
    {
        $prod = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Mi Borrador Tesis a Eliminar',
            'workflow_state' => 'draft',
        ]);
        $prod->users()->attach($this->student->id, ['role' => 'author']);

        $response = $this->actingAs($this->student)->delete(route('productions.destroy', $prod));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'El borrador ha sido eliminado con éxito.');

        $this->assertSoftDeleted('productions', [
            'id' => $prod->id,
        ]);
    }

    public function test_user_cannot_submit_other_users_draft(): void
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('Estudiante');

        $prod = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Borrador Ajeno',
            'workflow_state' => 'draft',
        ]);
        $prod->users()->attach($otherStudent->id, ['role' => 'author']);

        $response = $this->actingAs($this->student)->post(route('productions.submit-draft', $prod));
        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_non_draft_production(): void
    {
        $prod = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis En Revision',
            'workflow_state' => 'under_tutor_review',
        ]);
        $prod->users()->attach($this->student->id, ['role' => 'author']);

        $response = $this->actingAs($this->student)->delete(route('productions.destroy', $prod));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Solo los borradores pueden ser eliminados.');

        $this->assertDatabaseHas('productions', [
            'id' => $prod->id,
            'deleted_at' => null,
        ]);
    }
}
