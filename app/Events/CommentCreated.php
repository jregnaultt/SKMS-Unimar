<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\Production;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Comment $comment,
        public readonly Production $production,
        public readonly User $author
    ) {}
}
