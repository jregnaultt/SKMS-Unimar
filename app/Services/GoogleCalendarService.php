<?php

namespace App\Services;

use App\Models\Production;
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
    public function getHostForProduction(Production $production): ?User
    {
        // Priority: author > tutor > jury (represented as 'jury' in pivot table)
        foreach (['author', 'tutor', 'jury'] as $role) {
            $user = $production->users()->wherePivot('role', $role)->first();
            if ($user && $user->google_refresh_token) {
                return $user;
            }
        }

        return null;
    }

    protected function getClientForUser(User $user): ?Client
    {
        if (! $user->google_refresh_token) {
            return null;
        }

        $client = app(Client::class);
        $client->setClientId(config('services.google.client_id') ?? env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(config('services.google.client_secret') ?? env('GOOGLE_CLIENT_SECRET'));

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

                    // Autocuración: token invalidado o revocado en Google, lo limpiamos de la BD
                    $user->google_access_token = null;
                    $user->google_refresh_token = null;
                    $user->google_token_expires_at = null;
                    $user->save();

                    return null;
                }
                $user->google_access_token = $newToken['access_token'];
                $user->google_token_expires_at = now()->addSeconds($newToken['expires_in']);
                $user->save();
            } catch (\Exception $e) {
                Log::error("Exception refreshing Google token for user {$user->id}: ".$e->getMessage());

                // Autocuración: error al conectar, limpiamos credenciales para forzar nueva sincronización
                $user->google_access_token = null;
                $user->google_refresh_token = null;
                $user->google_token_expires_at = null;
                $user->save();

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

        $host = $this->getHostForProduction($production);
        if (! $host) {
            // Nadie ha sincronizado su cuenta de Google Calendar
            return false;
        }

        $client = $this->getClientForUser($host);
        if (! $client) {
            return false;
        }

        $service = new Calendar($client);

        // Build Event
        $event = new Event;
        $event->setSummary("SKMS Unimar: {$milestone->title}");

        $description = "Hito de entrega programado para el trabajo: \"{$production->title}\"\n";
        $description .= 'Materia: '.($production->subject?->name ?? 'N/A')."\n";
        $description .= 'Estado actual: '.strtoupper($production->workflow_state)."\n";
        $event->setDescription($description);

        // Date
        $scheduledDate = ($milestone->scheduled_date ?? now()->addDay())->copy()->shiftTimezone('America/Caracas');
        $start = new EventDateTime;
        $start->setDateTime($scheduledDate->format(\DateTime::RFC3339));
        $start->setTimeZone('America/Caracas');
        $event->setStart($start);

        $end = new EventDateTime;
        // Event lasts 1 hour
        $end->setDateTime($scheduledDate->copy()->addHour()->format(\DateTime::RFC3339));
        $end->setTimeZone('America/Caracas');
        $event->setEnd($end);

        // Attendees: invitamos a todos los participantes de la producción excepto al hospedador
        $attendees = [];
        $otherUsers = $production->users()->where('users.id', '!=', $host->id)->get();
        foreach ($otherUsers as $user) {
            if ($user->email) {
                $attendee = new EventAttendee;
                $attendee->setEmail($user->email);
                $attendees[] = $attendee;
            }
        }

        // Siempre invitar al correo de coordinación configurado
        $coordinationEmail = config('services.google.coordination_email');
        if ($coordinationEmail) {
            $attendee = new EventAttendee;
            $attendee->setEmail($coordinationEmail);
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

        $host = $this->getHostForProduction($production);
        if (! $host) {
            return false;
        }

        $client = $this->getClientForUser($host);
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
