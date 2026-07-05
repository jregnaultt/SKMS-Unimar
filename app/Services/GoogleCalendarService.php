<?php

namespace App\Services;

use App\Models\ProductionMilestone;
use App\Models\User;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected function getClientForUser(User $user): ?Client
    {
        if (! $user->google_refresh_token) {
            return null;
        }

        $client = new Client;
        $client->setClientId(config('services.google.client_id') ?? env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(config('services.google.client_secret') ?? env('GOOGLE_CLIENT_SECRET'));

        $client->setAccessToken([
            'access_token' => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at ? $user->google_token_expires_at->diffInSeconds(now(), false) : 0,
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

    public function syncMilestone(ProductionMilestone $milestone): bool
    {
        $production = $milestone->production;
        if (! $production) {
            return false;
        }

        // Get the student (author)
        $student = $production->users()->wherePivot('role', 'author')->first();
        if (! $student) {
            return false;
        }

        $client = $this->getClientForUser($student);
        if (! $client) {
            // Student has not connected Google Calendar
            return false;
        }

        $service = new Calendar($client);

        // Get the tutor to invite him
        $tutor = $production->users()->wherePivot('role', 'tutor')->first();

        // Build Event
        $event = new Event;
        $event->setSummary("SKMS Unimar: {$milestone->title}");

        $description = "Hito de entrega programado para el trabajo: \"{$production->title}\"\n";
        $description .= 'Materia: '.($production->subject?->name ?? 'N/A')."\n";
        $description .= 'Estado actual: '.strtoupper($production->workflow_state)."\n";
        $event->setDescription($description);

        // Date
        $scheduledDate = $milestone->scheduled_date ?? now()->addDay();
        $start = new EventDateTime;
        $start->setDateTime($scheduledDate->format(\DateTime::RFC3339));
        $start->setTimeZone('America/Caracas');
        $event->setStart($start);

        $end = new EventDateTime;
        // Event lasts 1 hour
        $end->setDateTime($scheduledDate->copy()->addHour()->format(\DateTime::RFC3339));
        $end->setTimeZone('America/Caracas');
        $event->setEnd($end);

        // Attendees
        $attendees = [];
        if ($tutor) {
            $attendee = new EventAttendee;
            $attendee->setEmail($tutor->email);
            $attendees[] = $attendee;
        }
        $event->setAttendees($attendees);

        try {
            if ($milestone->google_event_id) {
                // Update
                $service->events->update('primary', $milestone->google_event_id, $event);
            } else {
                // Insert
                $createdEvent = $service->events->insert('primary', $event);
                $milestone->google_event_id = $createdEvent->getId();
                $milestone->saveQuietly();
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Error syncing milestone {$milestone->id} to Google Calendar: ".$e->getMessage());

            return false;
        }
    }

    public function deleteMilestone(ProductionMilestone $milestone): bool
    {
        if (! $milestone->google_event_id) {
            return true;
        }

        $production = $milestone->production;
        if (! $production) {
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

        $service = new Calendar($client);

        try {
            $service->events->delete('primary', $milestone->google_event_id);
            $milestone->google_event_id = null;
            $milestone->saveQuietly();

            return true;
        } catch (\Exception $e) {
            Log::error("Error deleting Google Calendar event for milestone {$milestone->id}: ".$e->getMessage());

            return false;
        }
    }
}
