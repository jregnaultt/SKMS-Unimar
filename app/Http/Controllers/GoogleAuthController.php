<?php

namespace App\Http\Controllers;

use App\Jobs\SyncMilestoneToGoogleCalendarJob;
use Google\Client;
use Google\Service\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    protected function getGoogleClient(): Client
    {
        $client = app(Client::class);
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(route('google.callback'));
        $client->addScope(Calendar::CALENDAR_EVENTS);
        $client->addScope('https://www.googleapis.com/auth/drive');
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function redirect()
    {
        $client = $this->getGoogleClient();

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request)
    {
        if (! $request->has('code')) {
            return redirect()->route('dashboard')->with('error', 'Código de autorización de Google no proporcionado.');
        }

        try {
            $client = $this->getGoogleClient();
            $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

            if (isset($token['error'])) {
                return redirect()->route('dashboard')->with('error', 'Error al autenticar con Google: '.$token['error_description']);
            }

            $user = auth()->user();
            $user->google_access_token = $token['access_token'] ?? null;
            if (isset($token['refresh_token'])) {
                $user->google_refresh_token = $token['refresh_token'];
            }
            $user->google_token_expires_at = now()->addSeconds($token['expires_in'] ?? 3600);
            $user->save();

            // Sincronización retroactiva de hitos futuros de todas las producciones asociadas a este usuario
            $productions = $user->productions;
            foreach ($productions as $production) {
                $milestones = $production->milestones()
                    ->where('scheduled_date', '>=', now()->startOfDay())
                    ->get();
                foreach ($milestones as $milestone) {
                    dispatch(new SyncMilestoneToGoogleCalendarJob($milestone, 'sync'));
                }
            }

            return redirect()->route('dashboard')->with('success', '¡Cuenta de Google conectada con éxito! Calendario sincronizado.');
        } catch (\Exception $e) {
            Log::error('Google Auth Error: '.$e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Ocurrió un error inesperado al conectar tu cuenta: '.$e->getMessage());
        }
    }
}
