<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WorkflowTransitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $production = $this->route('production');
        $currentState = $production ? $production->workflow_state : null;

        return [
            'target_state' => 'required|in:under_tutor_review,under_jury_review,under_coordinator_review,needs_corrections,approved,published,rejected',
            'comment' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $target = $this->input('target_state');
                    if (in_array($target, ['needs_corrections', 'rejected']) && empty(trim($value ?? ''))) {
                        $fail('Se requiere un comentario justificativo para esta acción.');
                    }
                },
            ],
            'file_id' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($production, $currentState) {
                    $target = $this->input('target_state');
                    $isGoogleDoc = $production && ! empty($production->google_drive_file_id);
                    if ($currentState === 'needs_corrections' && $target === 'under_tutor_review' && ! $isGoogleDoc && empty($value)) {
                        $fail('Debe subir el documento corregido antes de enviar.');
                    }
                },
            ],
            'changelog' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($currentState) {
                    $target = $this->input('target_state');
                    if ($currentState === 'needs_corrections' && $target === 'under_tutor_review' && empty(trim($value ?? ''))) {
                        $fail('Debe describir los cambios realizados.');
                    }
                },
            ],
            'preassigned_jury_1_id' => 'nullable|exists:users,id|different:preassigned_jury_2_id',
            'preassigned_jury_2_id' => 'nullable|exists:users,id|different:preassigned_jury_1_id',
        ];
    }

    /**
     * Get the custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_state.required' => 'El estado destino es obligatorio.',
            'target_state.in' => 'El estado destino seleccionado no es válido.',
        ];
    }
}
