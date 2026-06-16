<?php

namespace App\Listeners;

use App\Enums\CommentStatus;
use App\Events\CommentCreated;
use App\Events\CommentStatusChanged;
use App\Models\User;
use App\Notifications\CommentNotification;

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
                $student->notify(new CommentNotification(
                    $production,
                    $event->comment,
                    $event->author,
                    'comment_created'
                ));
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

            $observer->notify(new CommentNotification(
                $production,
                $comment,
                $event->changedBy,
                'comment_addressed'
            ));
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
