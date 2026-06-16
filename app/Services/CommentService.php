<?php

namespace App\Services;

use App\Enums\CommentStatus;
use App\Events\CommentCreated;
use App\Events\CommentStatusChanged;
use App\Models\Comment;
use App\Models\Production;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CommentService
{
    /**
     * States in which new observations can be created.
     *
     * @var array<int, string>
     */
    protected array $reviewableStates = ['under_review', 'needs_corrections'];

    // ─── Authorization Checks ────────────────────────────────────────────────

    /**
     * Whether the user can create a root observation on the given production.
     */
    public function canCreateObservation(Production $production, User $user): bool
    {
        if (! in_array($production->workflow_state, $this->reviewableStates)) {
            return false;
        }

        if ($user->hasRole(['Coordinador', 'Super Admin'])) {
            return true;
        }

        $isTutor = $production->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'tutor')
            ->exists();

        $isJury = $production->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'jury')
            ->exists();

        return $isTutor || $isJury;
    }

    /**
     * Whether the user can transition the comment to the given status.
     */
    public function canChangeStatus(Comment $comment, User $user, CommentStatus $newStatus): bool
    {
        // Only allow sequential transitions
        if (! $comment->status->canTransitionTo($newStatus)) {
            return false;
        }

        $production = $comment->production;

        // Student (author) can mark pending → in_progress and in_progress → addressed
        if (in_array($newStatus, [CommentStatus::InProgress, CommentStatus::Addressed])) {
            return $production->users()
                ->where('user_id', $user->id)
                ->wherePivot('role', 'author')
                ->exists();
        }

        return false;
    }

    /**
     * Whether the user can verify/close an addressed comment (tutor/jury closing the loop).
     */
    public function canVerify(Comment $comment, User $user): bool
    {
        if ($comment->status !== CommentStatus::Addressed) {
            return false;
        }

        // Only the original observer (tutor/jury who posted the comment) can close it
        return $comment->user_id === $user->id;
    }

    // ─── Business Operations ─────────────────────────────────────────────────

    /**
     * Create a root observation on a production (Tutor/Jury only).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws \InvalidArgumentException
     */
    public function createObservation(Production $production, User $user, array $data): Comment
    {
        if (! in_array($production->workflow_state, $this->reviewableStates)) {
            throw new \InvalidArgumentException(
                "No se pueden crear observaciones cuando la producción está en estado '{$production->workflow_state}'."
            );
        }

        if (! $this->canCreateObservation($production, $user)) {
            throw new AuthorizationException(
                'Solo Tutores o Jurados asignados a esta producción pueden crear observaciones.'
            );
        }

        $comment = Comment::create([
            'production_id' => $production->id,
            'user_id' => $user->id,
            'content' => $data['content'],
            'reference_section' => $data['reference_section'] ?? null,
            'status' => CommentStatus::Pending->value,
            'parent_id' => null,
        ]);

        CommentCreated::dispatch($comment, $production, $user);

        return $comment;
    }

    /**
     * Create a reply from the student to an existing observation.
     *
     * @throws AuthorizationException
     */
    public function replyToObservation(Comment $parent, User $student, string $content): Comment
    {
        if ($parent->isReply()) {
            throw new \InvalidArgumentException('No se puede responder a una respuesta (solo 1 nivel de anidación).');
        }

        if ($parent->status === CommentStatus::Addressed) {
            throw new AuthorizationException('No se puede responder a una observación que ya fue atendida.');
        }

        $production = $parent->production;

        $isAuthor = $production->users()
            ->where('user_id', $student->id)
            ->wherePivot('role', 'author')
            ->exists();

        if (! $isAuthor) {
            throw new AuthorizationException('Solo el estudiante propietario puede responder observaciones.');
        }

        return Comment::create([
            'production_id' => $parent->production_id,
            'user_id' => $student->id,
            'content' => $content,
            'reference_section' => null,
            'status' => CommentStatus::Pending->value,
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * Transition a comment to a new status.
     *
     * @throws AuthorizationException
     * @throws \InvalidArgumentException
     */
    public function changeStatus(Comment $comment, User $user, CommentStatus $newStatus): void
    {
        if (! $comment->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Transición inválida: '{$comment->status->value}' → '{$newStatus->value}'."
            );
        }

        if (! $this->canChangeStatus($comment, $user, $newStatus)) {
            throw new AuthorizationException('No tienes permiso para cambiar el estado de esta observación.');
        }

        $previousStatus = $comment->status;
        $comment->update(['status' => $newStatus->value]);

        CommentStatusChanged::dispatch($comment, $previousStatus, $newStatus, $user);
    }

    /**
     * Verify/close an addressed comment (called by the original tutor/jury).
     *
     * This marks the observation loop as complete — the tutor confirms the correction was made.
     *
     * @throws AuthorizationException
     */
    public function verifyObservation(Comment $comment, User $user): void
    {
        if (! $this->canVerify($comment, $user)) {
            throw new AuthorizationException(
                'Solo el autor de la observación puede verificarla una vez que está atendida.'
            );
        }

        // Verification is a soft-close: we mark it as addressed (already is) and record the actor.
        // The actual "closed" state is implicit when status = addressed and the tutor has verified.
        // We touch updated_at to signal the verification timestamp.
        $comment->touch();
    }

    /**
     * Delete a comment (only the author, only if pending and without replies).
     *
     * @throws AuthorizationException
     * @throws \InvalidArgumentException
     */
    public function deleteComment(Comment $comment, User $user): void
    {
        if ($comment->user_id !== $user->id) {
            throw new AuthorizationException('Solo el autor puede eliminar su observación.');
        }

        if ($comment->status !== CommentStatus::Pending) {
            throw new \InvalidArgumentException('Solo se pueden eliminar observaciones en estado pendiente.');
        }

        if ($comment->hasReplies()) {
            throw new \InvalidArgumentException(
                'No se puede eliminar una observación que ya tiene respuestas (integridad histórica).'
            );
        }

        $comment->delete();
    }
}
