<?php

namespace App\Listeners;

use App\Enums\CommentStatus;
use App\Events\CommentCreated;
use App\Events\CommentStatusChanged;
use App\Models\Notification;
use App\Models\User;

class SendCommentNotification
{
    /**
     * Handle a CommentCreated event.
     * Notifies the student (author) that a new observation was posted on their production.
     */
    public function handleCommentCreated(CommentCreated $event): void
    {
        $production = $event->production;

        // Notify all authors of the production
        $production->users()
            ->wherePivot('role', 'author')
            ->each(function (User $student) use ($event, $production): void {
                Notification::create([
                    'user_id' => $student->id,
                    'type' => 'comment_created',
                    'title' => 'Nueva observación en tu producción',
                    'message' => sprintf(
                        '%s publicó una nueva observación en "%s".',
                        $event->author->name,
                        $production->title ?? 'Sin título'
                    ),
                    'data' => json_encode([
                        'production_id' => $production->id,
                        'comment_id' => $event->comment->id,
                        'author_id' => $event->author->id,
                        'reference_section' => $event->comment->reference_section,
                    ]),
                    'read' => false,
                ]);
            });
    }

    /**
     * Handle a CommentStatusChanged event.
     * - addressed: notifies the original tutor/jury that a correction was made
     * - verified (tutor touched the addressed comment): notifies the student
     */
    public function handleCommentStatusChanged(CommentStatusChanged $event): void
    {
        $comment = $event->comment;
        $production = $comment->production;

        if ($event->newStatus === CommentStatus::Addressed) {
            // Notify the original observer (tutor/jury who created the root comment)
            $rootComment = $comment->isReply() ? $comment->parent : $comment;
            $observer = $rootComment->user;

            Notification::create([
                'user_id' => $observer->id,
                'type' => 'comment_addressed',
                'title' => 'Observación atendida',
                'message' => sprintf(
                    'El estudiante ha marcado como atendida una observación en "%s". Por favor verifícala.',
                    $production->title ?? 'Sin título'
                ),
                'data' => json_encode([
                    'production_id' => $production->id,
                    'comment_id' => $comment->id,
                    'changed_by' => $event->changedBy->id,
                ]),
                'read' => false,
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            CommentCreated::class => 'handleCommentCreated',
            CommentStatusChanged::class => 'handleCommentStatusChanged',
        ];
    }
}
