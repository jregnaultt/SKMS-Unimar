<?php

namespace Tests\Feature;

use App\Jobs\ExportGoogleDocToPdfJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleDocIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicProgram $program;

    protected ResearchLine $line;

    protected ProductionType $type;

    protected AcademicPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Coordinador']);

        $this->program = AcademicProgram::create([
            'name' => 'Ingeniería de Sistemas',
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
            'description' => 'Trabajo especial de grado',
        ]);

        $this->period = AcademicPeriod::create([
            'name' => '2026-I',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);
    }

    public function test_production_store_validation_requires_file_or_google_doc(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        // Neither file_id nor google_drive_file_id provided
        $response = $this->actingAs($user)->post(route('productions.store'), [
            'title' => 'Tesis sin archivo',
            'abstract' => 'Resumen sin archivo',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'keywords' => 'keyword1',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'action' => 'draft',
        ]);

        $response->assertSessionHasErrors(['file_id', 'google_drive_file_id']);
    }

    public function test_store_production_with_google_doc_dispatches_export_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $response = $this->actingAs($user)->post(route('productions.store'), [
            'title' => 'Tesis con Google Docs',
            'abstract' => 'Resumen con Google Docs',
            'authors' => 'Autor de Prueba',
            'tutor' => 'Tutor de Prueba',
            'keywords' => 'ia, tesis',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'google_drive_file_id' => 'google-file-123456',
            'google_document_title' => 'Mi Tesis de Grado',
            'google_access_token' => 'ya29.access-token-xyz',
            'action' => 'submit',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('productions', [
            'title' => 'Tesis con Google Docs',
            'google_drive_file_id' => 'google-file-123456',
            'google_document_title' => 'Mi Tesis de Grado',
            'workflow_state' => 'under_review',
        ]);

        Queue::assertPushed(ExportGoogleDocToPdfJob::class, function ($job) {
            return $job->fileId === 'google-file-123456'
                && $job->accessToken === 'ya29.access-token-xyz';
        });
    }

    public function test_google_drive_service_exports_pdf_successfully(): void
    {
        Http::fake([
            'https://www.googleapis.com/*' => Http::response('FAKE_PDF_BINARY_CONTENT', 200),
        ]);

        $production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis Mock',
            'abstract' => 'Abstract',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'draft',
            'google_drive_file_id' => 'google-file-id',
        ]);

        $service = new GoogleDriveService;
        $result = $service->exportToPdf($production, 'google-file-id', 'test-token');

        $this->assertTrue($result);
        $this->assertTrue($production->hasMedia('documento'));

        $mediaPath = $production->getFirstMedia('documento')->getPath();
        $this->assertEquals('FAKE_PDF_BINARY_CONTENT', file_get_contents($mediaPath));
    }

    public function test_show_production_renders_google_docs_iframe(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de prueba',
            'abstract' => 'Resumen',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'under_review',
            'google_drive_file_id' => 'google-file-999999',
        ]);

        // Associate the student user as author so they are authorized to view it
        $production->users()->attach($user->id, ['role' => 'author']);

        $response = $this->actingAs($user)->get(route('productions.show', $production));

        $response->assertStatus(200);
        $response->assertSee('google-file-999999');
        $response->assertSee('Editar Documento');
        $response->assertSee('Sincronizar Cambios');
    }

    public function test_sync_google_doc_endpoint_refreshes_pdf_successfully(): void
    {
        Http::fake([
            'https://www.googleapis.com/*' => Http::response('FAKE_PDF_BINARY_CONTENT_UPDATED', 200),
        ]);

        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de prueba',
            'abstract' => 'Resumen',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'draft',
            'google_drive_file_id' => 'google-file-999999',
        ]);

        $production->users()->attach($user->id, ['role' => 'author']);

        $response = $this->actingAs($user)->postJson(route('productions.sync', $production), [
            'google_access_token' => 'new-access-token-999',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'document_url']);
        $this->assertEquals('¡Documento sincronizado con éxito!', $response->json('message'));

        $this->assertTrue($production->fresh()->hasMedia('documento'));
        $mediaPath = $production->fresh()->getFirstMedia('documento')->getPath();
        $this->assertEquals('FAKE_PDF_BINARY_CONTENT_UPDATED', file_get_contents($mediaPath));
    }
}
