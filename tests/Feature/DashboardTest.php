<?php

namespace Tests\Feature;

use App\Models\Production;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $tutor;

    private User $coordinator;

    private User $admin;

    private User $multiRoleUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Spatie Roles
        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Tutor']);
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Super Admin']);

        // 2. Create Users and assign roles
        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        $this->tutor = User::factory()->create();
        $this->tutor->assignRole('Tutor');

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinador');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->multiRoleUser = User::factory()->create();
        $this->multiRoleUser->assignRole(['Tutor', 'Coordinador']);
    }

    /**
     * Test guest cannot access the dashboard and is redirected to login.
     */
    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test student can access dashboard and receives student view data.
     */
    public function test_student_receives_student_dashboard(): void
    {
        // Create a production associated with student
        $production = Production::factory()->create();
        $production->users()->attach($this->student->id, ['role' => 'author']);

        $response = $this->actingAs($this->student)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('activeRole', 'Estudiante');
        $response->assertViewHas('data');

        $data = $response->viewData('data');
        $this->assertArrayHasKey('myProductions', $data);
        $this->assertCount(1, $data['myProductions']);
        $this->assertEquals($production->id, $data['activeProduction']->id);
    }

    /**
     * Test tutor can access dashboard and receives evaluator view data.
     */
    public function test_tutor_receives_tutor_dashboard(): void
    {
        // Create a production where tutor is assigned
        $production = Production::factory()->create();
        $production->users()->attach($this->tutor->id, ['role' => 'tutor']);

        $response = $this->actingAs($this->tutor)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('activeRole', 'Tutor');

        $data = $response->viewData('data');
        $this->assertArrayHasKey('productions', $data);
        $this->assertCount(1, $data['productions']);
    }

    /**
     * Test coordinator can access dashboard and receives coordinator view data.
     */
    public function test_coordinator_receives_coordinator_dashboard(): void
    {
        $response = $this->actingAs($this->coordinator)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('activeRole', 'Coordinador');

        $data = $response->viewData('data');
        $this->assertArrayHasKey('metrics', $data);
        $this->assertArrayHasKey('validationQueue', $data);
    }

    /**
     * Test admin can access dashboard and receives admin view data.
     */
    public function test_admin_receives_admin_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('activeRole', 'Super Admin');

        $data = $response->viewData('data');
        $this->assertArrayHasKey('auditLogs', $data);
    }

    /**
     * Test multi-role user can switch active dashboard role via session.
     */
    public function test_multi_role_user_can_switch_active_role(): void
    {
        // Login and verify default role is the first one (Tutor)
        $response = $this->actingAs($this->multiRoleUser)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('activeRole', 'Tutor');

        // Post switch role to Coordinador
        $switchResponse = $this->actingAs($this->multiRoleUser)
            ->post(route('dashboard.switch-role'), [
                'role' => 'Coordinador',
            ]);

        $switchResponse->assertRedirect(route('dashboard'));
        $switchResponse->assertSessionHas('success');
        $this->assertEquals('Coordinador', session('active_dashboard_role'));

        // Visit dashboard again and verify it is now coordinator view
        $finalResponse = $this->actingAs($this->multiRoleUser)
            ->get(route('dashboard'));

        $finalResponse->assertStatus(200);
        $finalResponse->assertViewHas('activeRole', 'Coordinador');
    }

    /**
     * Test user cannot switch to a role they do not possess.
     */
    public function test_user_cannot_switch_to_unauthorized_role(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('dashboard.switch-role'), [
                'role' => 'Coordinador',
            ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
        $this->assertNotEquals('Coordinador', session('active_dashboard_role'));
    }
}
