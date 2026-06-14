<?php

namespace App\Notifications;

use App\Models\Production;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProductionStateChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Production $production,
        public string $previousState,
        public string $newState,
        public string $title,
        public string $message,
        public ?string $comment = null
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
        return [
            'production_id' => $this->production->id,
            'title' => $this->title,
            'message' => $this->message,
            'previous_state' => $this->previousState,
            'new_state' => $this->newState,
            'comment' => $this->comment,
        ];
    }
}
