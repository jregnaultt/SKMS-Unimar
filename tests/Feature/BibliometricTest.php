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
