<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Production;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Production $production,
        public Comment $comment,
        public User $author,
        public string $notificationType // 'comment_created' or 'comment_addressed'
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->notificationType === 'comment_created'
            ? 'Nueva observación en tu producción'
            : 'Observación atendida';

        $message = $this->notificationType === 'comment_created'
            ? sprintf('%s publicó una nueva observación en "%s".', $this->author->name, $this->production->title ?? 'Sin título')
            : sprintf('El estudiante ha marcado como atendida una observación en "%s". Por favor verifícala.', $this->production->title ?? 'Sin título');

        return [
            'production_id' => $this->production->id,
            'comment_id' => $this->comment->id,
            'author_id' => $this->author->id,
            'type' => $this->notificationType,
            'title' => $title,
            'message' => $message,
            'reference_section' => $this->comment->reference_section,
        ];
    }
}
