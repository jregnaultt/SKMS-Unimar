<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowTransitionRequest;
use App\Models\Production;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
                'rejection_proposed' => 'Se ha propuesto el rechazo del documento a Coordinación.',
            ];

            $msg = $messages[$targetState] ?? 'Estado actualizado correctamente.';

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Ocurrió un error al procesar el cambio de estado: '.$e->getMessage());
        }
    }

    /**
     * View list of active rejection proposals.
     */
    public function rejectionProposals(Request $request): View
    {
        if (! $request->user()->hasRole(['Coordinador', 'Super Admin', 'Decano'])) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        $productions = Production::where('workflow_state', 'rejection_proposed')
            ->with(['academicProgram', 'academicPeriod', 'users'])
            ->latest()
            ->paginate(15);

        return view('pages.coordination.rejections', [
            'productions' => $productions,
        ]);
    }
}
