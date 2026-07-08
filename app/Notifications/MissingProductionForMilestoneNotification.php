<?php

namespace App\Notifications;

use App\Models\PeriodMilestone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MissingProductionForMilestoneNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PeriodMilestone $periodMilestone
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subjectName = $this->periodMilestone->subject?->name ?? 'N/A';

        return (new MailMessage)
            ->subject('SKMS: Registro de Trabajo de Investigación Requerido')
            ->line("Se ha programado una nueva actividad o hito académico obligatorio: \"{$this->periodMilestone->title}\" para la asignatura {$subjectName}.")
            ->line('Detectamos que aún no ha registrado su propuesta o trabajo de investigación en el sistema.')
            ->line('Es indispensable registrar su trabajo para que los hitos, entregas y la sincronización con Google Calendar se activen correctamente para usted y su tutor.')
            ->action('Registrar Trabajo de Investigación', route('productions.create'))
            ->line('Gracias por utilizar nuestro sistema de gestión científica.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $subjectName = $this->periodMilestone->subject?->name ?? 'N/A';

        return [
            'period_milestone_id' => $this->periodMilestone->id,
            'title' => 'Registro de Trabajo Pendiente para Hito Académico',
            'message' => "Se ha programado el hito \"{$this->periodMilestone->title}\" para la asignatura {$subjectName}, pero usted no ha registrado su propuesta o trabajo en el sistema. Por favor, regístrelo para activar las entregas.",
        ];
    }
}
