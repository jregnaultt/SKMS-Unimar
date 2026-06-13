<?php

namespace App\Listeners;

use App\Events\ClaimRejected;
use App\Mail\ClaimRejectedMail;
use Illuminate\Support\Facades\Mail;

class NotifyClaimRejected
{
    /**
     * Handle the event.
     */
    public function handle(ClaimRejected $event): void
    {
        Mail::to($event->claim->user->email)->send(new ClaimRejectedMail($event->claim));
    }
}
