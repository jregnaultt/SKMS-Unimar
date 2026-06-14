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

        if ($newState === 'under_review') {
            $title = 'Nueva producción científica por revisar';
            $message = "El trabajo \"{$production->title}\" ha sido enviado a revisión.";

            foreach ($tutors->merge($juries) as $evaluator) {
                $evaluator->notify(new ProductionStateChangedNotification(
                    $production,
                    $previousState,
                    $newState,
                    $title,
                    $message,
                    $comment
                ));
            }
        } elseif ($newState === 'needs_corrections') {
            $title = 'Se requieren correcciones';
            $message = "Tu trabajo \"{$production->title}\" requiere correcciones por parte del tutor/jurado.";

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
