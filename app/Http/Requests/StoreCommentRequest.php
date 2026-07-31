<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('annotation_position')) {
            $pos = $this->input('annotation_position');
            if (is_array($pos)) {
                $hasPage = isset($pos['page']) && $pos['page'] !== '';
                $hasX = isset($pos['x']) && $pos['x'] !== '';
                $hasY = isset($pos['y']) && $pos['y'] !== '';

                if (! $hasPage && ! $hasX && ! $hasY) {
                    $this->offsetUnset('annotation_position');
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:2000'],
            'reference_section' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'annotation_position' => ['nullable', 'array'],
            'annotation_position.page' => ['required_with:annotation_position', 'integer', 'min:1'],
            'annotation_position.x' => ['required_with:annotation_position', 'numeric', 'min:0', 'max:100'],
            'annotation_position.y' => ['required_with:annotation_position', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'El contenido de la observación es obligatorio.',
            'content.min' => 'La observación debe tener al menos :min caracteres.',
            'content.max' => 'La observación no puede exceder :max caracteres.',
            'reference_section.max' => 'La referencia de sección no puede exceder :max caracteres.',
            'parent_id.exists' => 'El comentario al que intenta responder no existe.',
        ];
    }
}
