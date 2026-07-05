<?php

namespace App\Jobs;

use App\Events\MetadataExtracted;
use App\Services\MetadataExtractorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExtractMetadataJob implements ShouldQueue
{
    use Queueable;

    public $userId;

    public $pdfPath;

    public $fileId;

    public $timeout = 180; // 3 minutos para dar tiempo a archivos pesados

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $pdfPath, $fileId)
    {
        $this->userId = $userId;
        $this->pdfPath = $pdfPath;
        $this->fileId = $fileId;
    }

    /**
     * Execute the job.
     */
    public function handle(MetadataExtractorService $extractorService): void
    {
        try {
            $metadata = $extractorService->extractMetadata($this->pdfPath);

            // Cache the extracted metadata for 2 hours to support the bulk import hybrid state recovery
            Cache::put("metadata_{$this->fileId}", $metadata, now()->addHours(2));

            event(new MetadataExtracted($this->userId, $this->fileId, $metadata));
        } catch (\Exception $e) {
            Log::error('Failed to extract metadata: '.$e->getMessage());
            throw $e;
        }
    }
}
