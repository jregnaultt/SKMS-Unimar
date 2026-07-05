<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowTransitionRequest;
use App\Models\Production;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;

class WorkflowController extends Controller
{
    /**
     * Inject WorkflowService.
     */
    public function __construct(protected WorkflowService $workflowService) {}

    /**
     * Transition the workflow state of a production.
     */
    public function transition(WorkflowTransitionRequest $request, Production $production): RedirectResponse
    {
        $targetState = $request->input('target_state');
        $user = $request->user();

        // 1. Perform authorization check
        if (! $this->workflowService->canTransition($production, $targetState, $user)) {
            abort(403, 'Transición de estado no autorizada para tu rol.');
        }

        try {
            // 2. Perform transition
            $this->workflowService->transition($production, $targetState, $user, $request->validated());

            $messages = [
                'under_tutor_review' => '¡El documento ha sido enviado a revisión del tutor exitosamente!',
                'under_jury_review' => '¡El documento ha sido enviado a revisión del jurado exitosamente!',
                'needs_corrections' => 'Se ha solicitado la corrección del documento con éxito.',
                'approved' => '¡Producción científica aprobada con éxito!',
                'rejected' => 'La producción científica ha sido rechazada.',
                'published' => '¡Producción científica publicada exitosamente en el repositorio!',
            ];

            $msg = $messages[$targetState] ?? 'Estado actualizado correctamente.';

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Ocurrió un error al procesar el cambio de estado: '.$e->getMessage());
        }
    }
}
