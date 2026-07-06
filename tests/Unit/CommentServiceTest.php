<?php

namespace Tests\Unit;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Production;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommentService $service;

    private Production $production;

    private User $student;

    private User $tutor;

    private User $jury;

    private User $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CommentService;

        $this->student = User::factory()->create();
        $this->tutor = User::factory()->create();
        $this->jury = User::factory()->create();
        $this->outsider = User::factory()->create();

        $this->production = Production::factory()->create(['workflow_state' => 'under_review']);

        $this->production->users()->attach($this->student->id, ['role' => 'author']);
        $this->production->users()->attach($this->tutor->id, ['role' => 'tutor']);
        $this->production->users()->attach($this->jury->id, ['role' => 'jury']);
    }

    // ── Case 1: Assigned tutor creates observation in under_review ────────────

    public function test_assigned_tutor_can_create_observation_when_under_review(): void
    {
        $comment = $this->service->createObservation($this->production, $this->tutor, [
            'content' => 'La metodología necesita más detalle en el muestreo.',
            'reference_section' => 'Sección 3.2',
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::Pending->value,
            'user_id' => $this->tutor->id,
        ]);
    }

    // ── Case 2: Unassigned tutor cannot create observation ────────────────────

    public function test_unassigned_user_cannot_create_observation(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->service->createObservation($this->production, $this->outsider, [
            'content' => 'Observación de un usuario no asignado.',
        ]);
    }

    // ── Case 3: Assigned jury can create observation ──────────────────────────

    public function test_assigned_jury_can_create_observation(): void
    {
        $comment = $this->service->createObservation($this->production, $this->jury, [
            'content' => 'El marco teórico requiere más fuentes recientes.',
            'reference_section' => 'Marco Teórico',
        ]);

        $this->assertEquals($this->jury->id, $comment->user_id);
        $this->assertEquals(CommentStatus::Pending, $comment->status);
    }

    // ── Case 4: Student cannot create a root observation ─────────────────────

    public function test_student_cannot_create_root_observation(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->service->createObservation($this->production, $this->student, [
            'content' => 'El estudiante no puede crear observaciones raíz.',
        ]);
    }

    // ── Case 5: Tutor cannot create observation when draft ────────────────────

    public function test_cannot_create_observation_when_draft(): void
    {
        $this->production->update(['workflow_state' => 'draft']);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->createObservation($this->production, $this->tutor, [
            'content' => 'Intento inválido en borrador.',
        ]);
    }

    // ── Case 6: Tutor cannot create observation when published ────────────────

    public function test_cannot_create_observation_when_published(): void
    {
        $this->production->update(['workflow_state' => 'published']);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->createObservation($this->production, $this->tutor, [
            'content' => 'Intento inválido en publicado.',
        ]);
    }

    // ── Case 7: Student can reply to a pending observation ────────────────────

    public function test_student_can_reply_to_pending_observation(): void
    {
        $observation = Comment::factory()->pending()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $reply = $this->service->replyToObservation(
            $observation,
            $this->student,
            'He actualizado la sección de metodología con más detalle.'
        );

        $this->assertEquals($observation->id, $reply->parent_id);
        $this->assertEquals($this->student->id, $reply->user_id);
    }

    // ── Case 8: Student cannot reply to an addressed observation ─────────────

    public function test_student_cannot_reply_to_addressed_observation(): void
    {
        $observation = Comment::factory()->addressed()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $this->expectException(AuthorizationException::class);

        $this->service->replyToObservation(
            $observation,
            $this->student,
            'Respuesta tardía a una observación cerrada.'
        );
    }

    // ── Case 9: Student can mark pending → in_progress ───────────────────────

    public function test_student_can_mark_pending_to_in_progress(): void
    {
        $comment = Comment::factory()->pending()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $this->service->changeStatus($comment, $this->student, CommentStatus::InProgress);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::InProgress->value,
        ]);
    }

    // ── Case 10: Student can transition pending → addressed ──────────────────

    public function test_student_can_transition_pending_directly_to_addressed(): void
    {
        $comment = Comment::factory()->pending()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $this->service->changeStatus($comment, $this->student, CommentStatus::Addressed);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::Addressed->value,
        ]);
    }

    // ── Case 11: Student can mark in_progress → addressed ────────────────────

    public function test_student_can_mark_in_progress_to_addressed(): void
    {
        $comment = Comment::factory()->inProgress()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $this->service->changeStatus($comment, $this->student, CommentStatus::Addressed);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::Addressed->value,
        ]);
    }

    // ── Case 12: Tutor can verify an addressed observation ────────────────────

    public function test_tutor_can_verify_addressed_observation(): void
    {
        $comment = Comment::factory()->addressed()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        // Should not throw
        $this->service->verifyObservation($comment, $this->tutor);

        // Verification touches updated_at — comment still exists and addressed
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::Addressed->value,
        ]);
    }

    // ── Case 13: Outsider cannot change the status ────────────────────────────

    public function test_outsider_cannot_change_status(): void
    {
        $comment = Comment::factory()->pending()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $this->expectException(AuthorizationException::class);

        $this->service->changeStatus($comment, $this->outsider, CommentStatus::InProgress);
    }

    // ── Case 14: Tutor can delete a pending observation without replies ────────

    public function test_tutor_can_delete_pending_comment_without_replies(): void
    {
        $comment = Comment::factory()->pending()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $this->service->deleteComment($comment, $this->tutor);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    // ── Case 15: Tutor cannot delete observation that has replies ─────────────

    public function test_tutor_cannot_delete_comment_with_replies(): void
    {
        $comment = Comment::factory()->pending()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        Comment::factory()->asReply($comment)->create(['user_id' => $this->student->id]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->deleteComment($comment, $this->tutor);
    }

    // ── Case 16: Tutor cannot delete an already-addressed observation ──────────

    public function test_tutor_cannot_delete_addressed_comment(): void
    {
        $comment = Comment::factory()->addressed()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->deleteComment($comment, $this->tutor);
    }
}
