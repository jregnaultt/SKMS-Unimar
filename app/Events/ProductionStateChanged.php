<?php

namespace App\Events;

use App\Models\Production;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionStateChanged
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Production $production,
        public string $previousState,
        public string $newState,
        public User $user,
        public ?string $comment = null
    ) {}
}
