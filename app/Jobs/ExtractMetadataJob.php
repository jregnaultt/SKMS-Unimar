<?php

namespace App\Jobs;

use App\Events\MetadataExtracted;
use App\Services\MetadataExtractorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExtractMetadataJob implements ShouldQueue
{
    use Queueable;

    public $userId;

    public $pdfPath;

    public $fileId;

    public $deleteAfterExtraction;

    public $timeout = 180; // 3 minutos para dar tiempo a archivos pesados

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $pdfPath, $fileId, $deleteAfterExtraction = false)
    {
        $this->userId = $userId;
        $this->pdfPath = $pdfPath;
        $this->fileId = $fileId;
        $this->deleteAfterExtraction = $deleteAfterExtraction;
    }

    /**
     * Execute the job.
     */
    public function handle(MetadataExtractorService $extractorService): void
    {
        $fullPath = Storage::disk('local')->path($this->pdfPath);

        try {
            $extractorService->removeExtraUnimarCoverPage($fullPath);
            $metadata = $extractorService->extractMetadata($fullPath);

            $payload = [
                'status' => 'completed',
                'metadata' => $metadata,
            ];

            // Cache the extracted metadata for 2 hours to support the bulk import hybrid state recovery
            Cache::put("metadata_{$this->fileId}", $payload, now()->addHours(2));

            event(new MetadataExtracted($this->userId, $this->fileId, $payload));
        } catch (\Exception $e) {
            Log::error('Failed to extract metadata: '.$e->getMessage());

            $errorPayload = [
                'status' => 'error',
                'error_message' => 'Falla en la extracción: '.$e->getMessage(),
                'metadata' => [
                    'title' => basename($fullPath),
                    'abstract' => '',
                    'authors' => '',
                    'tutor' => '',
                    'keywords' => '',
                ],
            ];

            Cache::put("metadata_{$this->fileId}", $errorPayload, now()->addHours(2));

            event(new MetadataExtracted($this->userId, $this->fileId, $errorPayload));
        } finally {
            if ($this->deleteAfterExtraction && file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
}
