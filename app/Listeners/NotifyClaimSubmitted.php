<?php

namespace App\Listeners;

use App\Events\ClaimSubmitted;
use App\Mail\ClaimSubmittedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotifyClaimSubmitted
{
    /**
     * Handle the event.
     */
    public function handle(ClaimSubmitted $event): void
    {
        $coordinators = User::role(['Coordinador', 'Super Admin'])->get();

        foreach ($coordinators as $coordinator) {
            Mail::to($coordinator->email)->send(new ClaimSubmittedMail($event->claim));
        }
    }
}
