<?php

namespace App\Services;

use App\Models\Production;
use Illuminate\Support\Facades\Http;

/**
 * Class GoogleDriveService
 *
 * Handles API interactions with Google Drive for exporting documents to PDF format.
 */
class GoogleDriveService
{
    /**
     * Exports a Google Doc to PDF format and attaches it to the production's media library.
     *
     * @param  Production  $production  The scientific production model.
     * @param  string  $fileId  The Google Drive unique file identifier.
     * @param  string  $accessToken  The temporary OAuth access token.
     * @return bool True if the export and attachment succeeded.
     *
     * @throws \Exception If the Google Drive API export request fails.
     */
    public function exportToPdf(Production $production, string $fileId, string $accessToken): bool
    {
        $response = Http::withToken($accessToken)
            ->get("https://www.googleapis.com/drive/v3/files/{$fileId}/export", [
                'mimeType' => 'application/pdf',
            ]);

        if (! $response->successful()) {
            throw new \Exception('Falla en la exportación de Google Drive a PDF: '.$response->body());
        }

        // Clean name to avoid filesystem conflicts
        $safeTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', $production->title);
        $fileName = $safeTitle.'_'.time().'.pdf';

        $production->addMediaFromString($response->body())
            ->usingFileName($fileName)
            ->toMediaCollection('documento');

        return true;
    }
}
