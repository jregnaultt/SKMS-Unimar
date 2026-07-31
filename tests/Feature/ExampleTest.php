<?php

namespace Tests\Feature;

use App\Models\Production;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_see_panel_on_show_page(): void
    {
        // 1. Setup Role
        Role::firstOrCreate(['name' => 'Estudiante']);

        // 2. Create Student User
        $student = User::factory()->create();
        $student->assignRole('Estudiante');

        // 3. Create Draft Production
        $production = Production::factory()->create([
            'workflow_state' => 'draft',
        ]);
        $production->users()->attach($student->id, ['role' => 'author']);

        // 4. Visit show page
        $response = $this->actingAs($student)
            ->get(route('productions.show', $production));

        $response->assertStatus(200);
        $response->assertSee('Panel de Control y Flujo');
        $response->assertSee('Enviar a Revisión del Tutor');
    }
}
