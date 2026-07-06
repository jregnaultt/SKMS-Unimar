<?php

namespace App\Services;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Production;
use App\Models\User;
use Google\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    /**
     * Synchronizes comments and replies from Google Docs to the local database.
     *
     * @throws \Exception If the Google Drive API request fails.
     */
    public function syncComments(Production $production, string $fileId, string $accessToken): void
    {
        $response = Http::withToken($accessToken)
            ->get("https://www.googleapis.com/drive/v3/files/{$fileId}/comments", [
                'fields' => 'comments(*)',
            ]);

        if (! $response->successful()) {
            throw new \Exception('Falla al obtener comentarios de Google Drive: '.$response->body());
        }

        $data = $response->json();
        $comments = $data['comments'] ?? [];

        foreach ($comments as $gComment) {
            $googleCommentId = $gComment['id'];
            $authorEmail = $gComment['author']['emailAddress'] ?? null;
            $authorName = $gComment['author']['displayName'] ?? 'Usuario de Google';
            $content = $gComment['content'] ?? '';
            $isResolved = $gComment['resolved'] ?? false;
            $createdAt = isset($gComment['createdTime']) ? now()->parse($gComment['createdTime']) : now();

            // Find matching user in SKMS
            $user = null;
            if ($authorEmail) {
                $user = User::where('email', $authorEmail)->first();
            }

            // Fallback: associate with the author of the production
            $userId = $user ? $user->id : $production->users()->wherePivot('role', 'author')->first()?->id;
            $contentWithAuthor = $user ? $content : "[Escrito por {$authorName}]: {$content}";

            // Find or create comment in SKMS
            $referenceSection = isset($gComment['quotedFileContent']['value'])
                ? Str::limit($gComment['quotedFileContent']['value'], 250)
                : null;

            // Preserve local workflow status: only sync to 'addressed' when Google resolves it.
            // Do NOT reset to 'pending' if the student has already marked it in_progress/addressed locally.
            $existingComment = Comment::where('production_id', $production->id)
                ->where('google_comment_id', $googleCommentId)
                ->first();

            $localStatus = $isResolved
                ? CommentStatus::Addressed->value
                : ($existingComment?->status->value ?? CommentStatus::Pending->value);

            $comment = Comment::updateOrCreate(
                [
                    'production_id' => $production->id,
                    'google_comment_id' => $googleCommentId,
                ],
                [
                    'user_id' => $userId ?? 1,
                    'content' => $contentWithAuthor,
                    'reference_section' => $referenceSection,
                    'resolved_in_google' => $isResolved,
                    'status' => $localStatus,
                    'created_at' => $createdAt,
                ]
            );

            // Sync replies
            $replies = $gComment['replies'] ?? [];
            foreach ($replies as $gReply) {
                $googleReplyId = $gReply['id'];
                $replyEmail = $gReply['author']['emailAddress'] ?? null;
                $replyName = $gReply['author']['displayName'] ?? 'Usuario de Google';
                $replyContent = $gReply['content'] ?? '';
                $replyCreatedAt = isset($gReply['createdTime']) ? now()->parse($gReply['createdTime']) : now();

                $replyUser = null;
                if ($replyEmail) {
                    $replyUser = User::where('email', $replyEmail)->first();
                }

                $replyUserId = $replyUser ? $replyUser->id : $production->users()->wherePivot('role', 'author')->first()?->id;
                $replyContentWithAuthor = $replyUser ? $replyContent : "[Escrito por {$replyName}]: {$replyContent}";

                Comment::updateOrCreate(
                    [
                        'production_id' => $production->id,
                        'google_reply_id' => $googleReplyId,
                    ],
                    [
                        'parent_id' => $comment->id,
                        'user_id' => $replyUserId ?? 1,
                        'content' => $replyContentWithAuthor,
                        'resolved_in_google' => $isResolved,
                        'status' => CommentStatus::Pending->value,
                        'created_at' => $replyCreatedAt,
                    ]
                );
            }
        }
    }

    /**
     * Get Google Client for a specific user, refreshing their token if expired.
     */
    protected function getClientForUser(User $user): ?Client
    {
        if (! $user->google_refresh_token) {
            return null;
        }

        $client = new Client;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));

        $client->setAccessToken([
            'access_token' => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at ? now()->diffInSeconds($user->google_token_expires_at, false) : 0,
            'created' => time(),
        ]);

        if ($client->isAccessTokenExpired()) {
            try {
                $newToken = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                if (isset($newToken['error'])) {
                    Log::error("Failed to refresh Google token for user {$user->id}: ".json_encode($newToken));

                    return null;
                }
                $user->google_access_token = $newToken['access_token'];
                $user->google_token_expires_at = now()->addSeconds($newToken['expires_in']);
                $user->save();
            } catch (\Exception $e) {
                Log::error("Exception refreshing Google token for user {$user->id}: ".$e->getMessage());

                return null;
            }
        }

        return $client;
    }

    /**
     * Sync comments for a given production using the author's credentials.
     */
    public function syncCommentsForProduction(Production $production): void
    {
        if (! $production->google_drive_file_id) {
            return;
        }

        $student = $production->users()->wherePivot('role', 'author')->first();
        if (! $student) {
            return;
        }

        $client = $this->getClientForUser($student);
        if (! $client) {
            return;
        }

        $accessToken = $client->getAccessToken()['access_token'] ?? null;
        if ($accessToken) {
            $this->syncComments($production, $production->google_drive_file_id, $accessToken);
        }
    }

    /**
     * Resolves a comment on Google Docs by posting a reply with action = resolve.
     */
    public function resolveComment(Production $production, string $googleCommentId): bool
    {
        if (! $production->google_drive_file_id) {
            return false;
        }

        $student = $production->users()->wherePivot('role', 'author')->first();
        if (! $student) {
            return false;
        }

        $client = $this->getClientForUser($student);
        if (! $client) {
            return false;
        }

        $accessToken = $client->getAccessToken()['access_token'] ?? null;
        if (! $accessToken) {
            return false;
        }

        $response = Http::withToken($accessToken)
            ->post("https://www.googleapis.com/drive/v3/files/{$production->google_drive_file_id}/comments/{$googleCommentId}/replies?fields=id", [
                'content' => 'Marcado como resuelto (atendido) desde el SKMS.',
                'action' => 'resolve',
            ]);

        if (! $response->successful()) {
            Log::error('Falla al resolver comentario en Google Drive: '.$response->body());

            return false;
        }

        return true;
    }

    /**
     * Posts a reply to a comment on Google Docs.
     *
     * @return string|null The Google reply ID if successful, null otherwise.
     */
    public function replyToComment(Production $production, string $googleCommentId, string $content): ?string
    {
        if (! $production->google_drive_file_id) {
            return null;
        }

        $student = $production->users()->wherePivot('role', 'author')->first();
        if (! $student) {
            return null;
        }

        $client = $this->getClientForUser($student);
        if (! $client) {
            return null;
        }

        $accessToken = $client->getAccessToken()['access_token'] ?? null;
        if (! $accessToken) {
            return null;
        }

        $response = Http::withToken($accessToken)
            ->post("https://www.googleapis.com/drive/v3/files/{$production->google_drive_file_id}/comments/{$googleCommentId}/replies?fields=id", [
                'content' => $content,
            ]);

        if (! $response->successful()) {
            Log::error('Falla al responder comentario en Google Drive: '.$response->body());

            return null;
        }

        return $response->json('id');
    }
}
