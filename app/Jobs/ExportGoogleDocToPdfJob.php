<?php

namespace App\Jobs;

use App\Models\Production;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Class ExportGoogleDocToPdfJob
 *
 * Queue job to handle asynchronous downloading and exporting of Google Docs to PDF.
 */
class ExportGoogleDocToPdfJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Production  $production  The production model to attach the PDF to.
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
     *
     * @param  GoogleDriveService  $driveService  The Google Drive integration service.
     *
     * @throws \Exception If the Google Drive API export request fails.
     */
    public function handle(GoogleDriveService $driveService): void
    {
        try {
            $driveService->exportToPdf($this->production, $this->fileId, $this->accessToken);
        } catch (\Exception $e) {
            Log::error('Falla al exportar el documento de Google a PDF: '.$e->getMessage(), [
                'production_id' => $this->production->id,
                'file_id' => $this->fileId,
            ]);
            throw $e;
        }
    }
}
