<?php

namespace App\Jobs;

use App\Models\Production;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Class SyncGoogleDocCommentsJob
 *
 * Queue job to handle asynchronous synchronization of comments from Google Docs.
 */
class SyncGoogleDocCommentsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Production  $production  The production model to sync comments for.
     * @param  string  $fileId  The Google Drive file identifier.
     * @param  string  $accessToken  The temporary Google OAuth access token.
     */
    public function __construct(
        public Production $production,
        public string $fileId,
        public string $accessToken
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleDriveService $driveService): void
    {
        try {
            $driveService->syncComments($this->production, $this->fileId, $this->accessToken);
        } catch (\Exception $e) {
            Log::error('Falla al sincronizar los comentarios de Google Drive: '.$e->getMessage(), [
                'production_id' => $this->production->id,
                'file_id' => $this->fileId,
            ]);
            throw $e;
        }
    }
}
