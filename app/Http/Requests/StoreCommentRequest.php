<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:2000'],
            'reference_section' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
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
