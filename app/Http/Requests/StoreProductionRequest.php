<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductionRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string'],
            'authors' => ['required', 'string', 'max:500'],
            'tutor' => ['required', 'string', 'max:255'],
            'keywords' => ['required', 'string', 'max:500'],
            'academic_program_id' => ['required', 'exists:academic_programs,id'],
            'research_line_id' => ['required', 'exists:research_lines,id'],
            'production_type_id' => ['required', 'exists:production_types,id'],
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'file_id' => ['required_without:google_drive_file_id', 'nullable', 'string'],
            'google_drive_file_id' => ['required_without:file_id', 'nullable', 'string'],
            'google_document_title' => ['nullable', 'string', 'max:255'],
            'google_access_token' => ['required_with:google_drive_file_id', 'nullable', 'string'],
            'action' => ['required', 'in:draft,submit'],
        ];
    }
}
