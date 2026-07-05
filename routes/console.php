<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Models\Production;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $productions = Production::whereIn('workflow_state', ['under_tutor_review', 'under_jury_review', 'needs_corrections'])
        ->whereNotNull('google_drive_file_id')
        ->get();

    $driveService = resolve(GoogleDriveService::class);

    foreach ($productions as $production) {
        try {
            $driveService->syncCommentsForProduction($production);
        } catch (Exception $e) {
            Log::error("Error in automated comments sync for production {$production->id}: ".$e->getMessage());
        }
    }
})->hourly();
