<?php

namespace App\Listeners;

use App\Events\ProductionStateChanged;
use App\Notifications\ProductionStateChangedNotification;

class SendStateChangeNotifications
{
    /**
     * Handle the event.
     */
    public function handle(ProductionStateChanged $event): void
    {
        $production = $event->production;
        $newState = $event->newState;
        $previousState = $event->previousState;
        $comment = $event->comment;

        $title = 'Actualización de producción';
        $message = "El trabajo \"{$production->title}\" cambió de estado.";

        // Find the author(s)
        $authors = $production->users()->wherePivot('role', 'author')->get();

        // Find assigned tutors and juries
        $tutors = $production->users()->wherePivot('role', 'tutor')->get();
        $juries = $production->users()->wherePivot('role', 'jury')->get();

        if ($previousState === 'under_tutor_review' && $newState === 'under_tutor_review') {
            $title = 'Solicitud de pase a Jurado';
            $authorName = $production->users()->wherePivot('role', 'author')->first()?->name ?? 'El estudiante';
            $message = "El estudiante \"{$authorName}\" ha solicitado el pase al jurado para su tesis.";

            foreach ($tutors as $tutor) {
                $tutor->notify(new ProductionStateChangedNotification(
                    $production,
                    $previousState,
                    $newState,
                    $title,
                    $message,
                    $comment
                ));
            }
        } elseif ($newState === 'under_tutor_review') {
            $title = 'Nueva producción científica por revisar (Tutor)';
            $message = "El trabajo \"{$production->title}\" ha sido enviado a tu revisión como tutor.";

            foreach ($tutors as $tutor) {
                $tutor->notify(new ProductionStateChangedNotification(
                    $production,
                    $previousState,
                    $newState,
                    $title,
                    $message,
                    $comment
                ));
            }
        } elseif ($newState === 'under_jury_review') {
            $title = 'Nueva producción científica por revisar (Jurado)';
            $message = "El trabajo \"{$production->title}\" ha sido enviado a tu revisión como jurado.";

            foreach ($juries as $jury) {
                $jury->notify(new ProductionStateChangedNotification(
                    $production,
                    $previousState,
                    $newState,
                    $title,
                    $message,
                    $comment
                ));
            }
        } elseif ($newState === 'needs_corrections') {
            $actor = $event->user;
            $actorName = $actor ? $actor->name : 'Un evaluador';
            $isJury = $actor && $actor->hasRole('Jurado');

            $title = 'Se requieren correcciones';
            $message = $isJury
                ? "El jurado \"{$actorName}\" ha solicitado correcciones en tu trabajo \"{$production->title}\"."
                : "Tu tutor \"{$actorName}\" ha solicitado correcciones en tu trabajo \"{$production->title}\".";

            foreach ($authors as $author) {
                $author->notify(new ProductionStateChangedNotification(
                    $production,
                    $previousState,
                    $newState,
                    $title,
                    $message,
                    $comment
                ));
            }

            if ($isJury) {
                // Notify tutor
                $tutorTitle = 'Jurado solicita correcciones a tu tutorado';
                $tutorMessage = "El jurado \"{$actorName}\" ha solicitado correcciones a tu tutorado en la tesis \"{$production->title}\".";
                foreach ($tutors as $tutor) {
                    $tutor->notify(new ProductionStateChangedNotification(
                        $production,
                        $previousState,
                        $newState,
                        $tutorTitle,
                        $tutorMessage,
                        $comment
                    ));
                }
            }
        } elseif ($newState === 'approved') {
            $title = '¡Trabajo Aprobado!';
            $message = "Felicidades, tu trabajo \"{$production->title}\" ha sido aprobado.";

            foreach ($authors as $author) {
                $author->notify(new ProductionStateChangedNotification(
                    $production,
                    $previousState,
                    $newState,
                    $title,
                    $message,
                    $comment
                ));
            }
        } elseif ($newState === 'rejected') {
            $title = 'Trabajo Rechazado';
            $message = "Lamentablemente, tu trabajo \"{$production->title}\" ha sido rechazado.";

            foreach ($authors as $author) {
                $author->notify(new ProductionStateChangedNotification(
                    $production,
                    $previousState,
                    $newState,
                    $title,
                    $message,
                    $comment
                ));
            }
        } elseif ($newState === 'published') {
            $title = '¡Trabajo Publicado!';
            $message = "Tu trabajo \"{$production->title}\" ha sido publicado oficialmente en el repositorio de ".config('app.name').'.';

            foreach ($authors as $author) {
                $author->notify(new ProductionStateChangedNotification(
                    $production,
                    $previousState,
                    $newState,
                    $title,
                    $message,
                    $comment
                ));
            }
        }
    }
}
