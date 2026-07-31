<?php

namespace Tests\Feature;

use App\Jobs\ExtractMetadataJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkProductionImportTest extends TestCase
{
    use RefreshDatabase;

    protected $coordinator;

    protected $student;

    protected $program;

    protected $line;

    protected $type;

    protected $period;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Spatie roles
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Estudiante']);

        // Create users
        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');

        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        // Create catalogs
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
            'description' => 'Trabajo especial de grado para optar al título',
        ]);

        $this->period = AcademicPeriod::create([
            'name' => '2026-I',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        Storage::fake('local');
    }

    public function test_coordinator_can_access_bulk_import_screen(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->get(route('admin.productions.import'));

        $response->assertStatus(200);
        $response->assertViewHasAll([
            'academicPeriods',
            'academicPrograms',
            'productionTypes',
            'researchLines',
        ]);
    }

    public function test_student_cannot_access_bulk_import_screen(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('admin.productions.import'));

        $response->assertStatus(403);
    }

    public function test_coordinator_can_upload_pdf_file_for_bulk(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->create('thesis.pdf', 1000); // 1MB PDF

        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.productions.import.upload'), [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['file_id', 'filename', 'status']);

        $fileId = $response->json('file_id');
        $this->assertNotNull($fileId);

        Storage::disk('local')->assertExists("temp_pdfs/{$fileId}.pdf");
        Queue::assertPushed(ExtractMetadataJob::class);
    }

    public function test_coordinator_can_check_status_of_extracted_metadata(): void
    {
        $fileId = 'test-uuid-123';
        $metadata = [
            'title' => 'Tesis IA de Prueba',
            'abstract' => 'Un resumen corto de prueba.',
            'authors' => 'José Ferreira',
            'tutor' => 'Prof. Maria',
            'keywords' => 'IA, Redes',
        ];

        Cache::put("metadata_{$fileId}", $metadata, 120);

        $response = $this->actingAs($this->coordinator)
            ->getJson(route('admin.productions.import.status').'?'.http_build_query([
                'file_ids' => [$fileId],
            ]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            $fileId => [
                'status' => 'completed',
                'metadata' => $metadata,
            ],
        ]);
    }

    public function test_coordinator_can_store_bulk_import_batch(): void
    {
        $fileId = 'test-uuid-999';
        Storage::disk('local')->put("temp_pdfs/{$fileId}.pdf", 'Fake PDF Content');

        $payload = [
            'productions' => [
                [
                    'file_id' => $fileId,
                    'title' => 'Diseño de un Repositorio Científico Inteligente',
                    'abstract' => 'Resumen detallado de la tesis histórica.',
                    'authors' => 'José Ferreira',
                    'tutor' => 'Profesor Tutor de Tesis',
                    'academic_program_id' => $this->program->id,
                    'research_line_id' => $this->line->id,
                    'production_type_id' => $this->type->id,
                    'academic_period_id' => $this->period->id,
                    'keywords' => 'Repositorio, IA, UNIMAR',
                ],
            ],
        ];

        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.productions.import.store'), $payload);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        // Assert database records
        $this->assertDatabaseHas('productions', [
            'title' => 'Diseño de un Repositorio Científico Inteligente',
            'authors' => 'José Ferreira',
            'tutor' => 'Profesor Tutor de Tesis',
            'workflow_state' => 'published',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
        ]);

        $production = Production::where('title', 'Diseño de un Repositorio Científico Inteligente')->first();
        $this->assertNotNull($production);

        // Verify media collection
        $this->assertCount(1, $production->getMedia('documento'));

        // Verify keywords
        $this->assertDatabaseHas('keywords', ['name' => 'Repositorio']);
        $this->assertDatabaseHas('keywords', ['name' => 'IA']);
        $this->assertDatabaseHas('keywords', ['name' => 'UNIMAR']);

        // Verify cache and temp files are cleared
        $this->assertFalse(Cache::has("metadata_{$fileId}"));
        Storage::disk('local')->assertMissing("temp_pdfs/{$fileId}.pdf");
    }

    public function test_historical_import_and_claim_suggestion_for_jose_ferreira(): void
    {
        // 1. Create the user Jose Ferreira
        $jose = User::factory()->create([
            'name' => 'José Ferreira',
            'email' => 'jferreira.5655@unimar.edu.ve',
        ]);
        $jose->assignRole('Estudiante');

        // 2. Perform bulk import for his thesis
        $fileId = 'jose-ferreira-uuid';
        Storage::disk('local')->put("temp_pdfs/{$fileId}.pdf", 'Fake PDF Content');

        $payload = [
            'productions' => [
                [
                    'file_id' => $fileId,
                    'title' => 'SISTEMA DE GESTIÓN DE CONOCIMIENTO CIENTÍFICO SKMS',
                    'abstract' => 'Resumen de la tesis de grado del estudiante José Ferreira.',
                    'authors' => 'JOSÉ FERREIRA',
                    'tutor' => 'DRA. MARIA',
                    'academic_program_id' => $this->program->id,
                    'research_line_id' => $this->line->id,
                    'production_type_id' => $this->type->id,
                    'academic_period_id' => $this->period->id,
                    'keywords' => 'SKMS, Repositorio',
                ],
            ],
        ];

        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.productions.import.store'), $payload);

        $response->assertRedirect(route('dashboard'));

        // 3. Act as Jose Ferreira and request dashboard to see if his thesis is suggested
        $dashResponse = $this->actingAs($jose)
            ->get(route('dashboard'));

        $dashResponse->assertStatus(200);

        // Assert the view has the suggested productions, including the newly imported one
        $suggested = $dashResponse->viewData('data')['suggestedProductions'];
        $this->assertNotNull($suggested);
        $this->assertCount(1, $suggested);
        $this->assertEquals('SISTEMA DE GESTIÓN DE CONOCIMIENTO CIENTÍFICO SKMS', $suggested->first()->title);
    }

    public function test_coordinator_can_store_single_import_production(): void
    {
        $fileId = 'test-single-uuid-111';
        Storage::disk('local')->put("temp_pdfs/{$fileId}.pdf", 'Fake Single PDF Content');

        $payload = [
            'file_id' => $fileId,
            'title' => 'Tesis Histórica Individual Inteligente',
            'abstract' => 'Resumen de la tesis de grado del estudiante individual.',
            'authors' => 'María Pérez',
            'tutor' => 'Profesor Tutor Individual',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'keywords' => 'Individual, Histórica, UNIMAR',
        ];

        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.productions.import.store-single'), $payload);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        // Assert database records
        $this->assertDatabaseHas('productions', [
            'title' => 'Tesis Histórica Individual Inteligente',
            'authors' => 'María Pérez',
            'tutor' => 'Profesor Tutor Individual',
            'workflow_state' => 'published',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
        ]);

        $production = Production::where('title', 'Tesis Histórica Individual Inteligente')->first();
        $this->assertNotNull($production);

        // Verify media collection
        $this->assertCount(1, $production->getMedia('documento'));

        // Verify keywords
        $this->assertDatabaseHas('keywords', ['name' => 'Individual']);
        $this->assertDatabaseHas('keywords', ['name' => 'Histórica']);

        // Verify cache and temp files are cleared
        $this->assertFalse(Cache::has("metadata_{$fileId}"));
        Storage::disk('local')->assertMissing("temp_pdfs/{$fileId}.pdf");
    }

    public function test_coordinator_cannot_upload_file_larger_than_5mb(): void
    {
        $file = UploadedFile::fake()->create('huge_thesis.pdf', 6000); // ~6MB PDF

        $response = $this->actingAs($this->coordinator)
            ->postJson(route('admin.productions.import.upload'), [
                'file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }
}
