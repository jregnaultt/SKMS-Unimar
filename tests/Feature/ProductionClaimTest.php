<?php

namespace Tests\Feature;

use App\Events\ClaimApproved;
use App\Events\ClaimRejected;
use App\Events\ClaimSubmitted;
use App\Models\Production;
use App\Models\ProductionClaim;
use App\Models\User;
use App\Services\ProductionClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard roles for testing
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Estudiante']);
    }

    public function test_suggested_productions_returns_correct_suggestions(): void
    {
        $user = User::factory()->create(['name' => 'Javier Andres Regnault']);

        // 1. Matches authors name partially and is published
        $prod1 = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de Javier',
            'authors' => 'Javier Regnault',
            'workflow_state' => 'published',
        ]);

        // 2. Matches tutor name partially and is published
        $prod2 = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Investigacion Guiada',
            'tutor' => 'Andres Regnault',
            'workflow_state' => 'published',
        ]);

        // 3. Match but is draft (should not suggest)
        $prod3 = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis Borrador',
            'authors' => 'Javier Regnault',
            'workflow_state' => 'draft',
        ]);

        // 4. Match but user is already officially linked
        $prod4 = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis Enlazada',
            'authors' => 'Javier Regnault',
            'workflow_state' => 'published',
        ]);
        $prod4->users()->attach($user->id, ['role' => 'author']);

        // 5. Match but already has an active claim
        $prod5 = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis Reclamada',
            'authors' => 'Javier Regnault',
            'workflow_state' => 'published',
        ]);
        ProductionClaim::create([
            'production_id' => $prod5->id,
            'user_id' => $user->id,
            'role' => 'author',
            'status' => 'pending',
        ]);

        $service = new ProductionClaimService;
        $suggestions = $service->suggestHistoricalProductions($user);

        $this->assertCount(2, $suggestions);
        $this->assertTrue($suggestions->contains('id', $prod1->id));
        $this->assertTrue($suggestions->contains('id', $prod2->id));
        $this->assertFalse($suggestions->contains('id', $prod3->id));
        $this->assertFalse($suggestions->contains('id', $prod4->id));
        $this->assertFalse($suggestions->contains('id', $prod5->id));
    }

    public function test_user_can_submit_claim(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $prod = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis Historica',
            'authors' => 'Javier Regnault',
            'workflow_state' => 'published',
        ]);

        $response = $this->actingAs($user)->post(route('claims.store'), [
            'production_id' => $prod->id,
            'role' => 'author',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('production_claims', [
            'production_id' => $prod->id,
            'user_id' => $user->id,
            'role' => 'author',
            'status' => 'pending',
        ]);

        Event::assertDispatched(ClaimSubmitted::class);
    }

    public function test_coordinator_can_approve_claim(): void
    {
        Event::fake();
        Mail::fake();

        $student = User::factory()->create();
        $student->assignRole('Estudiante');

        $coordinator = User::factory()->create();
        $coordinator->assignRole('Coordinador');

        $prod = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis Historica Unimar',
            'authors' => 'Javier Regnault',
            'workflow_state' => 'published',
        ]);

        $claim = ProductionClaim::create([
            'production_id' => $prod->id,
            'user_id' => $student->id,
            'role' => 'author',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($coordinator)->post(route('admin.claims.approve', $claim));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('production_claims', [
            'id' => $claim->id,
            'status' => 'approved',
            'resolved_by' => $coordinator->id,
        ]);

        $this->assertDatabaseHas('production_user', [
            'production_id' => $prod->id,
            'user_id' => $student->id,
            'role' => 'author',
        ]);

        Event::assertDispatched(ClaimApproved::class);
    }

    public function test_coordinator_can_reject_claim(): void
    {
        Event::fake();
        Mail::fake();

        $student = User::factory()->create();
        $student->assignRole('Estudiante');

        $coordinator = User::factory()->create();
        $coordinator->assignRole('Coordinador');

        $prod = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis Historica Unimar',
            'authors' => 'Javier Regnault',
            'workflow_state' => 'published',
        ]);

        $claim = ProductionClaim::create([
            'production_id' => $prod->id,
            'user_id' => $student->id,
            'role' => 'author',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($coordinator)->post(route('admin.claims.reject', $claim), [
            'rejection_reason' => 'El nombre no coincide adecuadamente con el documento físico.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('production_claims', [
            'id' => $claim->id,
            'status' => 'rejected',
            'resolved_by' => $coordinator->id,
            'rejection_reason' => 'El nombre no coincide adecuadamente con el documento físico.',
        ]);

        $this->assertDatabaseMissing('production_user', [
            'production_id' => $prod->id,
            'user_id' => $student->id,
        ]);

        Event::assertDispatched(ClaimRejected::class);
    }

    public function test_non_coordinators_cannot_resolve_claims(): void
    {
        $student1 = User::factory()->create();
        $student1->assignRole('Estudiante');

        $student2 = User::factory()->create();
        $student2->assignRole('Estudiante');

        $prod = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis Historica Unimar',
            'authors' => 'Javier Regnault',
            'workflow_state' => 'published',
        ]);

        $claim = ProductionClaim::create([
            'production_id' => $prod->id,
            'user_id' => $student1->id,
            'role' => 'author',
            'status' => 'pending',
        ]);

        // Non-coordinator index access
        $response = $this->actingAs($student2)->get(route('admin.claims.index'));
        $response->assertStatus(403);

        // Non-coordinator approval attempt
        $response = $this->actingAs($student2)->post(route('admin.claims.approve', $claim));
        $response->assertStatus(403);

        // Non-coordinator rejection attempt
        $response = $this->actingAs($student2)->post(route('admin.claims.reject', $claim), [
            'rejection_reason' => 'Intento malicioso.',
        ]);
        $response->assertStatus(403);

        $this->assertDatabaseHas('production_claims', [
            'id' => $claim->id,
            'status' => 'pending',
        ]);
    }
}
