<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateResearchLineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['Coordinador', 'Super Admin', 'Decano']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $lineId = $this->route('line')?->id;

        return [
            'name' => 'required|string|max:255|unique:research_lines,name,'.$lineId,
            'academic_program_id' => 'required|exists:academic_programs,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
