<?php

namespace App\Events;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Comment $comment,
        public readonly CommentStatus $previousStatus,
        public readonly CommentStatus $newStatus,
        public readonly User $changedBy
    ) {}
}
