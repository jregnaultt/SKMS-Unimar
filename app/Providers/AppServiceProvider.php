<?php

namespace App\Providers;

use App\Events\ClaimApproved;
use App\Events\ClaimRejected;
use App\Events\ClaimSubmitted;
use App\Listeners\NotifyClaimApproved;
use App\Listeners\NotifyClaimRejected;
use App\Listeners\NotifyClaimSubmitted;
use App\Listeners\SendCommentNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(ClaimSubmitted::class, NotifyClaimSubmitted::class);
        Event::listen(ClaimApproved::class, NotifyClaimApproved::class);
        Event::listen(ClaimRejected::class, NotifyClaimRejected::class);

        // Module 4: Feedback & Comments
        Event::subscribe(SendCommentNotification::class);
    }
}
