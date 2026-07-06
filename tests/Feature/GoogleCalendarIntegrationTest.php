<?php

namespace Tests\Feature;

use App\Jobs\SyncMilestoneToGoogleCalendarJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionMilestone;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Google\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleCalendarIntegrationTest extends TestCase
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

    public function test_login_redirects_always_to_dashboard_bypassing_intended_url(): void
    {
        $user = User::factory()->create();

        // 1. Intentamos entrar a una ruta protegida (ej: profile) lo que guardará el 'intended' URL en sesión.
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));

        // 2. Iniciamos sesión y verificamos que redirija estrictamente a dashboard (no a /profile).
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_google_auth_callback_enqueues_retroactive_sync_of_future_milestones(): void
    {
        Queue::fake();

        $student = User::factory()->create();
        $student->assignRole('Estudiante');

        // Creamos producción con el estudiante como autor
        $production = Production::create([
            'title' => 'Tesis de Grado Inteligente',
            'abstract' => 'Resumen',
            'authors' => 'Autor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'draft',
        ]);
        $production->users()->attach($student->id, ['role' => 'author']);

        // Hito futuro (debe encolarse)
        $futureMilestone = ProductionMilestone::create([
            'production_id' => $production->id,
            'title' => 'Entrega de Capitulo 1',
            'type' => 'delivery',
            'scheduled_date' => now()->addDays(5),
        ]);

        // Hito pasado (no debe encolarse)
        $pastMilestone = ProductionMilestone::create([
            'production_id' => $production->id,
            'title' => 'Propuesta inicial',
            'type' => 'delivery',
            'scheduled_date' => now()->subDays(5),
        ]);

        // Limpiar eventos disparados por la creación inicial de los hitos
        Queue::fake();

        // Mock del cliente Google
        $mockClient = \Mockery::mock(Client::class);
        $mockClient->shouldReceive('setClientId', 'setClientSecret', 'setRedirectUri', 'addScope', 'setAccessType', 'setPrompt', 'setAccessToken')->andReturnNull();
        $mockClient->shouldReceive('fetchAccessTokenWithAuthCode')->andReturn([
            'access_token' => 'mock-access-token',
            'refresh_token' => 'mock-refresh-token',
            'expires_in' => 3600,
        ]);
        $this->app->instance(Client::class, $mockClient);

        // Hacemos GET a la ruta callback
        $response = $this->actingAs($student)->get(route('google.callback', ['code' => 'mock_code']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        // Confirmar guardado de tokens
        $student->refresh();
        $this->assertEquals('mock-access-token', $student->google_access_token);
        $this->assertEquals('mock-refresh-token', $student->google_refresh_token);

        // Confirmar que SOLO el hito futuro fue encolado para sincronización
        Queue::assertPushed(SyncMilestoneToGoogleCalendarJob::class, function ($job) use ($futureMilestone) {
            return $job->milestone->id === $futureMilestone->id && $job->action === 'sync';
        });

        Queue::assertNotPushed(SyncMilestoneToGoogleCalendarJob::class, function ($job) use ($pastMilestone) {
            return $job->milestone->id === $pastMilestone->id;
        });
    }

    public function test_smart_host_selection_priority_order(): void
    {
        $student = User::factory()->create(['google_refresh_token' => 'student-token']);
        $tutor = User::factory()->create(['google_refresh_token' => 'tutor-token']);
        $jury = User::factory()->create(['google_refresh_token' => 'jury-token']);

        $production = Production::create([
            'title' => 'Tesis de Grado Inteligente',
            'abstract' => 'Resumen',
            'authors' => 'Autor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
        ]);

        $production->users()->attach($student->id, ['role' => 'author']);
        $production->users()->attach($tutor->id, ['role' => 'tutor']);
        $production->users()->attach($jury->id, ['role' => 'jury']);

        $calendarService = new GoogleCalendarService;

        // 1. Cuando todos tienen token, Estudiante (author) es el hospedador
        $host = $calendarService->getHostForProduction($production);
        $this->assertEquals($student->id, $host->id);

        // 2. Si estudiante no tiene token, Tutor es el hospedador
        $student->google_refresh_token = null;
        $student->save();
        $host = $calendarService->getHostForProduction($production);
        $this->assertEquals($tutor->id, $host->id);

        // 3. Si Tutor tampoco tiene token, Jurado (jury) es el hospedador
        $tutor->google_refresh_token = null;
        $tutor->save();
        $host = $calendarService->getHostForProduction($production);
        $this->assertEquals($jury->id, $host->id);

        // 4. Si ninguno tiene token, retorna null
        $jury->google_refresh_token = null;
        $jury->save();
        $host = $calendarService->getHostForProduction($production);
        $this->assertNull($host);
    }
}
