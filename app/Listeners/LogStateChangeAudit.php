<?php

namespace App\Listeners;

use App\Events\ProductionStateChanged;
use App\Models\AuditLog;
use App\Models\Production;

class LogStateChangeAudit
{
    /**
     * Handle the event.
     */
    public function handle(ProductionStateChanged $event): void
    {
        AuditLog::create([
            'user_id' => $event->user->id,
            'action' => 'workflow_transition',
            'auditable_type' => Production::class,
            'auditable_id' => $event->production->id,
            'old_values' => ['workflow_state' => $event->previousState],
            'new_values' => [
                'workflow_state' => $event->newState,
                'comment' => $event->comment,
            ],
            'ip_address' => request()->ip(),
        ]);
    }
}
