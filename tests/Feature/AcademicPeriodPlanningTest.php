<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Enrollment;
use App\Models\PeriodMilestone;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\Subject;
use App\Models\SubjectTutorPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicPeriodPlanningTest extends TestCase
{
    use RefreshDatabase;

    protected User $coordinator;

    protected User $student;

    protected User $tutor;

    protected AcademicPeriod $period;

    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Tutor']);

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');

        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        $this->tutor = User::factory()->create();
        $this->tutor->assignRole('Tutor');

        $this->period = AcademicPeriod::create([
            'name' => '2026-I',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'name' => 'Trabajo de Grado I',
        ]);

        // Create default academic structures for foreign keys
        $program = AcademicProgram::create([
            'name' => 'Ingeniería de Sistemas',
            'code' => 'ING-SIS',
            'activo' => true,
        ]);

        ResearchLine::create([
            'name' => 'Inteligencia Artificial',
            'academic_program_id' => $program->id,
            'activo' => true,
        ]);

        ProductionType::create([
            'name' => 'Trabajo de Grado',
            'descripcion' => 'Tesis',
        ]);
    }

    public function test_coordinator_can_assign_tutor_to_subject_in_period(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.periods.tutors.store', $this->period), [
                'subject_id' => $this->subject->id,
                'tutor_id' => $this->tutor->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subject_tutor_periods', [
            'academic_period_id' => $this->period->id,
            'subject_id' => $this->subject->id,
            'tutor_id' => $this->tutor->id,
        ]);
    }

    public function test_coordinator_can_enroll_student_with_active_tutor(): void
    {
        // First, make tutor active in the subject/period
        SubjectTutorPeriod::create([
            'academic_period_id' => $this->period->id,
            'subject_id' => $this->subject->id,
            'tutor_id' => $this->tutor->id,
        ]);

        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.periods.enrollments.store', $this->period), [
                'student_id' => $this->student->id,
                'subject_id' => $this->subject->id,
                'tutor_id' => $this->tutor->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollments', [
            'academic_period_id' => $this->period->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'tutor_id' => $this->tutor->id,
        ]);
    }

    public function test_cannot_enroll_student_with_inactive_tutor(): void
    {
        // Tutor is not active in this subject/period
        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.periods.enrollments.store', $this->period), [
                'student_id' => $this->student->id,
                'subject_id' => $this->subject->id,
                'tutor_id' => $this->tutor->id,
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $this->student->id,
        ]);
    }

    public function test_student_production_creation_prepopulates_and_copies_milestones(): void
    {
        // 1. Setup tutor availability and student enrollment
        SubjectTutorPeriod::create([
            'academic_period_id' => $this->period->id,
            'subject_id' => $this->subject->id,
            'tutor_id' => $this->tutor->id,
        ]);

        Enrollment::create([
            'academic_period_id' => $this->period->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'tutor_id' => $this->tutor->id,
        ]);

        // 2. Setup period milestones (global and tutor group)
        $globalMilestone = PeriodMilestone::create([
            'academic_period_id' => $this->period->id,
            'subject_id' => $this->subject->id,
            'tutor_id' => null, // global
            'type' => 'delivery',
            'title' => 'Entrega Capitulo I',
            'scheduled_date' => now()->addDays(5),
        ]);

        $groupMilestone = PeriodMilestone::create([
            'academic_period_id' => $this->period->id,
            'subject_id' => $this->subject->id,
            'tutor_id' => $this->tutor->id, // tutor group
            'type' => 'defense',
            'title' => 'Defensa Grupo',
            'scheduled_date' => now()->addDays(10),
        ]);

        // 3. Request creation form as student (to see if pre-populated)
        $response = $this->actingAs($this->student)
            ->get(route('productions.create'));

        $response->assertStatus(200);
        $response->assertSee($this->subject->name);
        $response->assertSee($this->tutor->name);

        // 4. Save production
        $prodType = ProductionType::first();
        $prog = AcademicProgram::first();
        $line = ResearchLine::first();

        // Note: ProductionController expects a file in storage/temp_pdfs to process.
        // Let's create a fake PDF to satisfy the controller
        Storage::disk('local')->put('temp_pdfs/dummy_file_id.pdf', 'PDF dummy content');

        $response = $this->actingAs($this->student)
            ->post(route('productions.store'), [
                'title' => 'Mi trabajo cientifico',
                'abstract' => 'Un resumen corto',
                'authors' => $this->student->name,
                'tutor_id' => $this->tutor->id,
                'subject_id' => $this->subject->id,
                'academic_program_id' => $prog->id,
                'research_line_id' => $line->id,
                'production_type_id' => $prodType->id,
                'academic_period_id' => $this->period->id,
                'keywords' => 'test, artificial, intelligence',
                'action' => 'draft',
                'file_id' => 'dummy_file_id',
            ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('productions', [
            'title' => 'Mi trabajo cientifico',
            'subject_id' => $this->subject->id,
        ]);

        // Verify milestones were copied
        $this->assertDatabaseHas('production_milestones', [
            'period_milestone_id' => $globalMilestone->id,
            'title' => 'Entrega Capitulo I',
        ]);

        $this->assertDatabaseHas('production_milestones', [
            'period_milestone_id' => $groupMilestone->id,
            'title' => 'Defensa Grupo',
        ]);
    }

    public function test_coordinator_can_search_students_by_name_cedula_or_email(): void
    {
        $student1 = User::factory()->create([
            'name' => 'John Doe Student',
            'email' => 'john.student@example.com',
            'cedula' => 'V-12345678',
        ]);
        $student1->assignRole('Estudiante');

        $student2 = User::factory()->create([
            'name' => 'Jane Smith Student',
            'email' => 'jane.student@example.com',
            'cedula' => 'V-87654321',
        ]);
        $student2->assignRole('Estudiante');

        // 1. Search by name
        $response = $this->actingAs($this->coordinator)
            ->get(route('admin.students.search', ['q' => 'John']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'John Doe Student']);
        $response->assertJsonMissing(['name' => 'Jane Smith Student']);

        // 2. Search by email
        $response = $this->actingAs($this->coordinator)
            ->get(route('admin.students.search', ['q' => 'jane.student']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Jane Smith Student']);
        $response->assertJsonMissing(['name' => 'John Doe Student']);

        // 3. Search by cedula
        $response = $this->actingAs($this->coordinator)
            ->get(route('admin.students.search', ['q' => '12345678']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'John Doe Student']);
        $response->assertJsonMissing(['name' => 'Jane Smith Student']);
    }

    public function test_coordinator_can_create_tutor_group_milestone_with_student_exclusions(): void
    {
        $studentA = User::factory()->create(['name' => 'Student A']);
        $studentA->assignRole('Estudiante');

        $studentB = User::factory()->create(['name' => 'Student B']);
        $studentB->assignRole('Estudiante');

        $tutor = User::factory()->create(['name' => 'Tutor X']);
        $tutor->assignRole('Tutor');

        // Enroll both students under this tutor for the subject
        $enrollmentA = Enrollment::create([
            'academic_period_id' => $this->period->id,
            'student_id' => $studentA->id,
            'subject_id' => $this->subject->id,
            'tutor_id' => $tutor->id,
        ]);

        $enrollmentB = Enrollment::create([
            'academic_period_id' => $this->period->id,
            'student_id' => $studentB->id,
            'subject_id' => $this->subject->id,
            'tutor_id' => $tutor->id,
        ]);

        // Create period milestone for the tutor group, excluding Student A
        $response = $this->actingAs($this->coordinator)
            ->post(route('admin.periods.milestones.store', $this->period), [
                'subject_id' => $this->subject->id,
                'tutor_id' => $tutor->id,
                'type' => 'delivery',
                'title' => 'Entrega con Exclusión',
                'scheduled_date' => now()->addDays(5)->toDateTimeString(),
                'excluded_students' => [$studentA->id],
            ]);

        $response->assertRedirect();

        // Retrieve the created period milestone
        $pm = PeriodMilestone::where('title', 'Entrega con Exclusión')->first();
        $this->assertNotNull($pm);
        $this->assertContains($studentA->id, $pm->excluded_student_ids);

        // Fetch productions
        $productionA = Production::whereHas('users', function ($query) use ($studentA) {
            $query->where('users.id', $studentA->id)->where('role', 'author');
        })->first();

        $productionB = Production::whereHas('users', function ($query) use ($studentB) {
            $query->where('users.id', $studentB->id)->where('role', 'author');
        })->first();

        $this->assertNotNull($productionA);
        $this->assertNotNull($productionB);

        // Verify Student A does NOT have a milestone
        $this->assertDatabaseMissing('production_milestones', [
            'production_id' => $productionA->id,
            'period_milestone_id' => $pm->id,
        ]);

        // Verify Student B DOES have a milestone
        $this->assertDatabaseHas('production_milestones', [
            'production_id' => $productionB->id,
            'period_milestone_id' => $pm->id,
        ]);
    }
}
