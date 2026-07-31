<?php

namespace Tests\Feature;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Production;
use App\Models\User;
use App\Notifications\CommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackCommentsTest extends TestCase
{
    use RefreshDatabase;

    private Production $production;

    private User $student;

    private User $tutor;

    private User $jury;

    private User $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create();
        $this->tutor = User::factory()->create();
        $this->jury = User::factory()->create();
        $this->outsider = User::factory()->create();

        $this->production = Production::factory()->create(['workflow_state' => 'under_tutor_review']);

        $this->production->users()->attach($this->student->id, ['role' => 'author']);
        $this->production->users()->attach($this->tutor->id, ['role' => 'tutor']);
        $this->production->users()->attach($this->jury->id, ['role' => 'jury']);
    }

    // ─── Scenario 1: Complete Happy Path ──────────────────────────────────────

    /**
     * Full flow: Tutor creates → student marks in_progress → replies → marks addressed → tutor verifies
     */
    public function test_full_happy_path_observation_lifecycle(): void
    {
        // 1. Tutor creates observation
        $this->actingAs($this->tutor)
            ->post(route('comments.store', $this->production), [
                'content' => 'La metodología no especifica el tamaño de muestra del estudio.',
                'reference_section' => 'Sección 3.2',
            ])
            ->assertRedirect();

        $observation = Comment::where('production_id', $this->production->id)
            ->whereNull('parent_id')
            ->first();

        $this->assertNotNull($observation);
        $this->assertEquals(CommentStatus::Pending->value, $observation->status->value);

        // 2. Student marks as in_progress
        $this->actingAs($this->student)
            ->patch(route('comments.update-status', $observation), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'id' => $observation->id,
            'status' => CommentStatus::InProgress->value,
        ]);

        // 3. Student replies
        $this->actingAs($this->student)
            ->post(route('comments.reply', $observation), [
                'content' => 'He actualizado la sección metodológica indicando una muestra de 50 participantes.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'parent_id' => $observation->id,
            'user_id' => $this->student->id,
        ]);

        // 4. Student marks as addressed
        $this->actingAs($this->student)
            ->patch(route('comments.update-status', $observation), ['status' => 'addressed'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'id' => $observation->id,
            'status' => CommentStatus::Addressed->value,
        ]);

        // 5. Tutor verifies
        $this->actingAs($this->tutor)
            ->post(route('comments.verify', $observation))
            ->assertRedirect();

        // Observation still exists and addressed
        $this->assertDatabaseHas('comments', [
            'id' => $observation->id,
            'status' => CommentStatus::Addressed->value,
        ]);
    }

    public function test_tutor_can_create_observation_with_annotation_position(): void
    {
        $this->actingAs($this->tutor)
            ->post(route('comments.store', $this->production), [
                'content' => 'Falta justificación en el segundo párrafo.',
                'reference_section' => 'Pág 5',
                'annotation_position' => [
                    'page' => 5,
                    'x' => 45.2,
                    'y' => 20.8,
                ],
            ])
            ->assertRedirect();

        $observation = Comment::where('production_id', $this->production->id)
            ->whereNull('parent_id')
            ->first();

        $this->assertNotNull($observation);
        $this->assertNotNull($observation->annotation_position);
        $this->assertEquals(5, $observation->annotation_position['page']);
        $this->assertEquals(45.2, $observation->annotation_position['x']);
        $this->assertEquals(20.8, $observation->annotation_position['y']);
    }

    // ─── Scenario 2: Role Authorization ───────────────────────────────────────

    public function test_outsider_cannot_create_observation(): void
    {
        $this->actingAs($this->outsider)
            ->post(route('comments.store', $this->production), [
                'content' => 'Observación de usuario no autorizado a la producción.',
            ])
            ->assertForbidden();
    }

    public function test_unassigned_tutor_cannot_create_observation(): void
    {
        $unassignedTutor = User::factory()->create();

        $this->actingAs($unassignedTutor)
            ->post(route('comments.store', $this->production), [
                'content' => 'Observación de tutor no asignado a la producción.',
            ])
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('comments.store', $this->production), [
            'content' => 'Observación sin autenticar.',
        ])
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_create_root_observation(): void
    {
        $this->actingAs($this->student)
            ->post(route('comments.store', $this->production), [
                'content' => 'El estudiante no debería poder crear observaciones raíz.',
            ])
            ->assertForbidden();
    }

    // ─── Scenario 3: Form Validation ──────────────────────────────────────────

    public function test_empty_content_fails_validation(): void
    {
        $this->actingAs($this->tutor)
            ->post(route('comments.store', $this->production), [
                'content' => '',
            ])
            ->assertSessionHasErrors('content');
    }

    public function test_content_too_short_fails_validation(): void
    {
        $this->actingAs($this->tutor)
            ->post(route('comments.store', $this->production), [
                'content' => 'Corto',
            ])
            ->assertSessionHasErrors('content');
    }

    public function test_invalid_status_value_fails_validation(): void
    {
        $comment = Comment::factory()->pending()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $this->actingAs($this->student)
            ->patch(route('comments.update-status', $comment), ['status' => 'invalid_status'])
            ->assertSessionHasErrors('status');
    }

    // ─── Scenario 4: Direct State Transitions ─────────────────────────────────

    public function test_can_transition_pending_directly_to_addressed(): void
    {
        $comment = Comment::factory()->pending()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $response = $this->actingAs($this->student)
            ->patch(route('comments.update-status', $comment), ['status' => 'addressed']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertEquals('addressed', $comment->fresh()->status->value);
    }

    // ─── Scenario 5: DB Notifications ─────────────────────────────────────────

    public function test_student_receives_notification_when_observation_is_created(): void
    {
        $this->actingAs($this->tutor)
            ->post(route('comments.store', $this->production), [
                'content' => 'Notificación: El marco teórico necesita más fuentes académicas.',
                'reference_section' => 'Marco Teórico',
            ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->student->id,
            'notifiable_type' => User::class,
            'type' => CommentNotification::class,
        ]);

        $notification = $this->student->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('comment_created', $notification->data['type']);
    }

    public function test_tutor_receives_notification_when_observation_is_addressed(): void
    {
        $comment = Comment::factory()->inProgress()->create([
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
        ]);

        $this->actingAs($this->student)
            ->patch(route('comments.update-status', $comment), ['status' => 'addressed']);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->tutor->id,
            'notifiable_type' => User::class,
            'type' => CommentNotification::class,
        ]);

        $notification = $this->tutor->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('comment_addressed', $notification->data['type']);
    }

    // ─── Scenario 6: Audit Trail ──────────────────────────────────────────────

    public function test_comment_creation_is_stored_in_database(): void
    {
        $this->actingAs($this->tutor)
            ->post(route('comments.store', $this->production), [
                'content' => 'Auditoría: Este comentario debe quedar registrado en la base de datos.',
                'reference_section' => 'Conclusiones',
            ]);

        $this->assertDatabaseHas('comments', [
            'production_id' => $this->production->id,
            'user_id' => $this->tutor->id,
            'status' => CommentStatus::Pending->value,
            'reference_section' => 'Conclusiones',
        ]);
    }

    // ─── Scenario 7: Published Production is Read-Only ────────────────────────

    public function test_cannot_create_observation_on_published_production(): void
    {
        $this->production->update(['workflow_state' => 'published']);

        $this->actingAs($this->tutor)
            ->post(route('comments.store', $this->production), [
                'content' => 'Intento de observación en producción publicada.',
            ])
            ->assertRedirect();

        // No comment should be created
        $this->assertDatabaseMissing('comments', [
            'production_id' => $this->production->id,
        ]);
    }

    public function test_cannot_create_observation_on_draft_production(): void
    {
        $this->production->update(['workflow_state' => 'draft']);

        $this->actingAs($this->tutor)
            ->post(route('comments.store', $this->production), [
                'content' => 'Intento de observación en producción en borrador.',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('comments', [
            'production_id' => $this->production->id,
        ]);
    }

    public function test_it_allows_storing_comment_with_empty_annotation_position_fields(): void
    {
        $this->actingAs($this->tutor)
            ->post(route('comments.store', $this->production), [
                'content' => 'Esta es una observación normal sin anotación en coordenadas.',
                'annotation_position' => [
                    'page' => '',
                    'x' => '',
                    'y' => '',
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'production_id' => $this->production->id,
            'content' => 'Esta es una observación normal sin anotación en coordenadas.',
            'annotation_position' => null,
        ]);
    }
}
