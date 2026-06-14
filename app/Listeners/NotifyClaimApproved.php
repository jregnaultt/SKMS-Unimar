<?php

namespace App\Listeners;

use App\Events\ClaimApproved;
use App\Mail\ClaimApprovedMail;
use Illuminate\Support\Facades\Mail;

class NotifyClaimApproved
{
    /**
     * Handle the event.
     */
    public function handle(ClaimApproved $event): void
    {
        Mail::to($event->claim->user->email)->send(new ClaimApprovedMail($event->claim));
    }
}
