<?php

namespace App\Http\Requests\CommandCentre;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOnboardingProposalRequest extends FormRequest
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
            'payload' => ['required', 'array'],
            'payload.project' => ['required', 'array'],
            'payload.project.name' => ['required', 'string', 'max:255'],
            'payload.project.objective' => ['nullable', 'string'],
            'payload.team' => ['nullable', 'array'],
            'payload.tasks' => ['nullable', 'array'],
            'payload.decisions' => ['nullable', 'array'],
            'payload.reminders' => ['nullable', 'array'],
            'summary' => ['nullable', 'string'],
        ];
    }
}
