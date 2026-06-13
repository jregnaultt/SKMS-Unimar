<?php

namespace App\Jobs;

use App\Events\MetadataExtracted;
use App\Services\MetadataExtractorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
            $text = $extractorService->extractText($this->pdfPath);

            $metadata = [
                'title' => $extractorService->extractTitle($text),
                'abstract' => $extractorService->extractAbstract($text),
                'keywords' => $extractorService->extractKeywords($text),
                'authors' => $extractorService->extractAuthors($text),
                'tutor' => $extractorService->extractTutor($text),
            ];

            event(new MetadataExtracted($this->userId, $this->fileId, $metadata));
        } catch (\Exception $e) {
            Log::error('Failed to extract text from PDF: '.$e->getMessage());
        }
    }
}
