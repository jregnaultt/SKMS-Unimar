<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Keyword;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
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

    public function test_unauthenticated_user_cannot_access_catalog(): void
    {
        $response = $this->get(route('catalog.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_access_catalog(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertStatus(200);
        $response->assertViewIs('catalog.index');
        $response->assertSee('Catálogo de Producción Científica');
    }

    public function test_catalog_returns_only_published_productions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $this->createProduction('published', ['title' => 'Tesis Publicada']);
        $this->createProduction('draft', ['title' => 'Tesis Borrador']);
        $this->createProduction('under_review', ['title' => 'Tesis En Revisión']);
        $this->createProduction('approved', ['title' => 'Tesis Aprobada']);

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertStatus(200);
        $response->assertSee('Tesis Publicada');
        $response->assertDontSee('Tesis Borrador');
        $response->assertDontSee('Tesis En Revisión');
        $response->assertDontSee('Tesis Aprobada');
    }

    public function test_catalog_filters_by_program_line_and_year(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $programB = AcademicProgram::create([
            'name' => 'Derecho',
            'code' => 'DER',
            'is_active' => true,
        ]);

        $lineB = ResearchLine::create([
            'academic_program_id' => $programB->id,
            'name' => 'Derecho Constitucional',
            'is_active' => true,
        ]);

        $this->createProduction('published', [
            'title' => 'Sistemas Inteligentes',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'published_at' => '2026-03-15 10:00:00',
        ]);

        $this->createProduction('published', [
            'title' => 'Leyes y Normas',
            'academic_program_id' => $programB->id,
            'research_line_id' => $lineB->id,
            'published_at' => '2025-05-20 10:00:00',
        ]);

        // Filter by program
        $response = $this->actingAs($user)->get(route('catalog.index', ['program' => $this->program->id]));
        $response->assertSee('Sistemas Inteligentes');
        $response->assertDontSee('Leyes y Normas');

        // Filter by line
        $response = $this->actingAs($user)->get(route('catalog.index', ['line' => $lineB->id]));
        $response->assertSee('Leyes y Normas');
        $response->assertDontSee('Sistemas Inteligentes');

        // Filter by year
        $response = $this->actingAs($user)->get(route('catalog.index', ['year' => 2025]));
        $response->assertSee('Leyes y Normas');
        $response->assertDontSee('Sistemas Inteligentes');
    }

    public function test_catalog_fulltext_searches(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $prod1 = $this->createProduction('published', [
            'title' => 'Desarrollo de Software Educativo',
            'abstract' => 'Este trabajo propone un framework para el aprendizaje móvil.',
            'authors' => 'Juan Pérez',
        ]);

        $this->createProduction('published', [
            'title' => 'Estudios sobre Blockchain',
            'abstract' => 'Análisis de la descentralización financiera.',
            'authors' => 'María Gómez',
        ]);

        // Search title
        $response = $this->actingAs($user)->get(route('catalog.index', ['q' => 'Software']));
        $response->assertSee('Desarrollo de Software Educativo');
        $response->assertDontSee('Estudios sobre Blockchain');

        // Search abstract
        $response = $this->actingAs($user)->get(route('catalog.index', ['q' => 'financiera']));
        $response->assertSee('Estudios sobre Blockchain');
        $response->assertDontSee('Desarrollo de Software Educativo');

        // Search author
        $response = $this->actingAs($user)->get(route('catalog.index', ['q' => 'Pérez']));
        $response->assertSee('Desarrollo de Software Educativo');
        $response->assertDontSee('Estudios sobre Blockchain');

        // Search keyword
        $keyword = Keyword::create(['name' => 'Educación']);
        $prod1->keywords()->attach($keyword);

        $response = $this->actingAs($user)->get(route('catalog.index', ['q' => 'Educación']));
        $response->assertSee('Desarrollo de Software Educativo');
        $response->assertDontSee('Estudios sobre Blockchain');
    }

    public function test_catalog_filters_by_type_and_tutor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $typeB = ProductionType::create([
            'name' => 'Artículo Científico',
            'description' => 'Artículo publicado en revista',
        ]);

        $this->createProduction('published', [
            'title' => 'Sistemas Inteligentes',
            'production_type_id' => $this->type->id,
            'tutor' => 'Dr. Carlos Mendoza',
        ]);

        $this->createProduction('published', [
            'title' => 'Leyes y Normas',
            'production_type_id' => $typeB->id,
            'tutor' => 'Dra. Ana Silva',
        ]);

        // Filter by type
        $response = $this->actingAs($user)->get(route('catalog.index', ['type' => $typeB->id]));
        $response->assertSee('Leyes y Normas');
        $response->assertDontSee('Sistemas Inteligentes');

        // Filter by tutor
        $response = $this->actingAs($user)->get(route('catalog.index', ['tutor' => 'Dr. Carlos Mendoza']));
        $response->assertSee('Sistemas Inteligentes');
        $response->assertDontSee('Leyes y Normas');
    }

    protected function createProduction(string $state, array $overrides = []): Production
    {
        return Production::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de prueba '.Str::random(5),
            'abstract' => 'Resumen de prueba',
            'authors' => 'Autor de Prueba',
            'tutor' => 'Tutor de Prueba',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => $state,
            'submission_date' => now(),
            'approval_date' => in_array($state, ['approved', 'published']) ? now() : null,
            'published_at' => $state === 'published' ? ($overrides['published_at'] ?? now()) : null,
        ], $overrides));
    }

    public function test_catalog_search_supports_query_method(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $this->createProduction('published', ['title' => 'Software Educativo']);

        $response = $this->actingAs($user)->json('QUERY', route('catalog.query'), [
            'titulo' => 'Software',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Software Educativo');
    }

    public function test_catalog_search_supports_post_fallback(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $this->createProduction('published', ['title' => 'Software Educativo']);

        $response = $this->actingAs($user)->postJson(route('catalog.query'), [
            'titulo' => 'Software',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Software Educativo');
    }
}
