<?php

namespace App\Jobs;

use App\Models\ProductionMilestone;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncMilestoneToGoogleCalendarJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ProductionMilestone $milestone,
        public string $action = 'sync'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleCalendarService $calendarService): void
    {
        if ($this->action === 'delete') {
            $calendarService->deleteMilestone($this->milestone);
        } else {
            $calendarService->syncMilestone($this->milestone);
        }
    }
}
