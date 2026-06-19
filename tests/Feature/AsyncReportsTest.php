<?php

namespace Tests\Feature;

use App\Events\ReportGenerated;
use App\Jobs\GenerateReportJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AsyncReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Estudiante']);

        // 2. Setup Users
        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');

        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        // 3. Fake storage disk
        Storage::fake('local');
    }

    /**
     * Test authorization for report dashboard.
     */
    public function test_authorization_for_reports_dashboard(): void
    {
        // Coordinator can access
        $this->actingAs($this->coordinator)
            ->get(route('admin.reports.index'))
            ->assertStatus(200);

        // Student cannot access
        $this->actingAs($this->student)
            ->get(route('admin.reports.index'))
            ->assertStatus(403);
    }

    /**
     * Test dispatching generate report enqueues the Job correctly.
     */
    public function test_coordinator_can_request_report_generation(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->coordinator)
            ->postJson(route('admin.reports.generate'), [
                'type' => 'excel',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'queued',
            ]);

        Queue::assertPushed(GenerateReportJob::class);
    }

    /**
     * Test report job generates Excel file and broadcasts event.
     */
    public function test_generate_report_job_compiles_excel_successfully(): void
    {
        Event::fake();

        // Create some data
        $program = AcademicProgram::factory()->create(['name' => 'Ingeniería de Sistemas']);
        $period = AcademicPeriod::factory()->create(['name' => '2026-I']);
        Production::factory()->count(2)->create([
            'academic_program_id' => $program->id,
            'academic_period_id' => $period->id,
            'workflow_state' => 'published',
        ]);

        $job = new GenerateReportJob($this->coordinator, 'excel', [
            'program_id' => $program->id,
            'period_id' => $period->id,
            'state' => 'published',
        ]);

        $job->handle();

        // Assert file exists on local storage
        $files = Storage::disk('local')->allFiles('reports');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.xlsx', $files[0]);

        // Assert broadcast event was dispatched
        Event::assertDispatched(ReportGenerated::class, function ($event) use ($files) {
            return $event->userId === $this->coordinator->id &&
                $event->filename === basename($files[0]);
        });
    }

    /**
     * Test report job generates PDF file and broadcasts event.
     */
    public function test_generate_report_job_compiles_pdf_successfully(): void
    {
        Event::fake();

        // Create some data
        $program = AcademicProgram::factory()->create();
        $period = AcademicPeriod::factory()->create();
        Production::factory()->count(2)->create([
            'academic_program_id' => $program->id,
            'academic_period_id' => $period->id,
            'workflow_state' => 'published',
        ]);

        $job = new GenerateReportJob($this->coordinator, 'pdf', [
            'program_id' => $program->id,
            'period_id' => $period->id,
            'state' => 'published',
        ]);

        $job->handle();

        // Assert PDF file is created
        $files = Storage::disk('local')->allFiles('reports');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.pdf', $files[0]);

        // Assert event was dispatched
        Event::assertDispatched(ReportGenerated::class);
    }

    /**
     * Test secure download checks.
     */
    public function test_reports_secure_download_checks(): void
    {
        // Try downloading non-existent file
        $this->actingAs($this->coordinator)
            ->get(route('admin.reports.download', ['filename' => 'invalid_report.pdf']))
            ->assertStatus(404);

        // Put a fake file
        Storage::disk('local')->put('reports/report_valid.pdf', 'fake PDF content');

        // Download valid file
        $response = $this->actingAs($this->coordinator)
            ->get(route('admin.reports.download', ['filename' => 'report_valid.pdf']))
            ->assertStatus(200);

        $response->assertHeader('Content-Disposition', 'attachment; filename=report_valid.pdf');
    }
}
