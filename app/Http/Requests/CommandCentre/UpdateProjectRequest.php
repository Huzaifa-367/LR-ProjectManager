<?php

namespace App\Http\Requests\CommandCentre;

use App\Enums\ProjectHealth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'next_action' => ['nullable', 'string', 'max:500'],
            'health' => ['nullable', Rule::enum(ProjectHealth::class)],
        ];
    }
}
