<?php

namespace App\Events;

use App\Models\ProductionClaim;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClaimApproved
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public ProductionClaim $claim) {}
}
