<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use App\Services\BibliometricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BibliometricTest extends TestCase
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
        Role::firstOrCreate(['name' => 'Tutor']);
        Role::firstOrCreate(['name' => 'Jurado']);

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

    public function test_coordinator_can_view_bibliometrics_dashboard(): void
    {
        $coordinator = User::factory()->create();
        $coordinator->assignRole('Coordinador');

        $response = $this->actingAs($coordinator)->get(route('bibliometrics.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.bibliometrics.index');
        $response->assertSee('Análisis Bibliométrico');
    }

    public function test_student_cannot_view_bibliometrics_dashboard(): void
    {
        $student = User::factory()->create();
        $student->assignRole('Estudiante');

        $response = $this->actingAs($student)->get(route('bibliometrics.index'));

        $response->assertStatus(403);
    }

    public function test_dashboard_counts_only_published_productions(): void
    {
        $coordinator = User::factory()->create();
        $coordinator->assignRole('Coordinador');

        $this->createProduction('published');
        $this->createProduction('published');
        $this->createProduction('approved');
        $this->createProduction('draft');

        $response = $this->actingAs($coordinator)->get(route('bibliometrics.index'));

        $response->assertStatus(200);
        $metrics = $response->viewData('metrics');
        $this->assertEquals(2, $metrics['total_published']);
    }

    public function test_service_groups_by_program_and_research_line(): void
    {
        $this->createProduction('published');
        $this->createProduction('published');

        $service = new BibliometricService;
        $byProgram = $service->productivityByProgram();
        $byLine = $service->productivityByResearchLine();

        $this->assertEquals('Ingeniería de Sistemas', $byProgram[0]['program']);
        $this->assertEquals(2, $byProgram[0]['total']);
        $this->assertEquals('Inteligencia Artificial', $byLine[0]['line']);
        $this->assertEquals(2, $byLine[0]['total']);
    }

    public function test_service_ranks_tutors(): void
    {
        $this->createProduction('published', ['tutor' => 'Prof. A']);
        $this->createProduction('published', ['tutor' => 'Prof. A']);
        $this->createProduction('published', ['tutor' => 'Prof. B']);

        $service = new BibliometricService;
        $topTutors = $service->topTutors();

        $this->assertEquals('Prof. A', $topTutors[0]['tutor']);
        $this->assertEquals(2, $topTutors[0]['total']);
        $this->assertEquals('Prof. B', $topTutors[1]['tutor']);
    }

    public function test_service_returns_yearly_evolution(): void
    {
        $this->createProduction('published', ['published_at' => '2025-03-15 10:00:00']);
        $this->createProduction('published', ['published_at' => '2026-05-20 10:00:00']);

        $service = new BibliometricService;
        $evolution = $service->yearlyEvolution();

        $this->assertCount(2, $evolution);
        $this->assertEquals(2025, $evolution[0]['year']);
        $this->assertEquals(2026, $evolution[1]['year']);
    }

    public function test_service_total_views_and_downloads_excludes_non_approved_non_published(): void
    {
        // 1. Draft (should be excluded)
        $this->createProduction('draft', ['views_count' => 10, 'downloads_count' => 5]);

        // 2. Approved (should be included)
        $this->createProduction('approved', ['views_count' => 20, 'downloads_count' => 10]);

        // 3. Published (should be included)
        $this->createProduction('published', ['views_count' => 30, 'downloads_count' => 15]);

        $service = new BibliometricService;

        $this->assertEquals(50, $service->totalViews());
        $this->assertEquals(25, $service->totalDownloads());
    }

    public function test_views_not_incremented_for_draft_on_show_route(): void
    {
        $coordinator = User::factory()->create();
        $coordinator->assignRole('Coordinador');

        $production = $this->createProduction('draft', ['views_count' => 0]);

        $response = $this->actingAs($coordinator)->get(route('productions.show', $production));

        $response->assertStatus(200);
        $this->assertEquals(0, $production->fresh()->views_count);
    }

    public function test_views_incremented_for_published_on_show_route(): void
    {
        $coordinator = User::factory()->create();
        $coordinator->assignRole('Coordinador');

        $production = $this->createProduction('published', ['views_count' => 0]);

        $response = $this->actingAs($coordinator)->get(route('productions.show', $production));

        $response->assertStatus(200);
        $this->assertEquals(1, $production->fresh()->views_count);
    }

    public function test_downloads_not_incremented_for_draft_on_download_route_when_forbidden(): void
    {
        $student = User::factory()->create();
        $student->assignRole('Estudiante');

        // Draft is not published, and student is not associated, so it should abort 403 and NOT increment download
        $production = $this->createProduction('draft', ['downloads_count' => 0]);

        $response = $this->actingAs($student)->get(route('productions.document', $production));

        $response->assertStatus(403);
        $this->assertEquals(0, $production->fresh()->downloads_count);
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
}
