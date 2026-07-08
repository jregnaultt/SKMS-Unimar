<?php

namespace Tests\Feature;

use App\Models\AcademicProgram;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_lifecycle_events_are_automatically_audited(): void
    {
        $user = User::factory()->create();

        // 1. Assert that no audit log is created if no user is authenticated (public/guest context)
        $program = AcademicProgram::create([
            'name' => 'Programa de Prueba',
            'code' => 'TEST-001',
            'description' => 'Programa de prueba para auditoria',
            'is_active' => true,
        ]);

        $this->assertEquals(0, AuditLog::count());

        // Authenticate the user
        $this->actingAs($user);

        // 2. Create model under authenticated user context
        $program2 = AcademicProgram::create([
            'name' => 'Segundo Programa',
            'code' => 'TEST-002',
            'description' => 'Segundo programa de prueba',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'create_academicprogram',
            'auditable_type' => AcademicProgram::class,
            'auditable_id' => $program2->id,
        ]);

        // 3. Update model
        $program2->update([
            'name' => 'Segundo Programa Modificado',
            'description' => 'Descripcion modificada',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'update_academicprogram',
            'auditable_type' => AcademicProgram::class,
            'auditable_id' => $program2->id,
        ]);

        $updateLog = AuditLog::where('action', 'update_academicprogram')->first();
        $this->assertNotNull($updateLog);
        $this->assertEquals('Segundo Programa', $updateLog->old_values['name']);
        $this->assertEquals('Segundo Programa Modificado', $updateLog->new_values['name']);

        // 4. Delete model
        $program2->delete();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'delete_academicprogram',
            'auditable_type' => AcademicProgram::class,
            'auditable_id' => $program2->id,
        ]);
    }
}
