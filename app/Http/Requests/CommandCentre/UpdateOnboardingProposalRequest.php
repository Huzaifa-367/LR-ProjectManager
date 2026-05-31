<?php

namespace App\Http\Requests\CommandCentre;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOnboardingProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $payload = $this->input('payload');

        if (! is_array($payload)) {
            return;
        }

        foreach (['team', 'tasks', 'decisions', 'reminders'] as $key) {
            if (! isset($payload[$key]) || ! is_string($payload[$key])) {
                continue;
            }

            $decoded = json_decode($payload[$key], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload[$key] = $decoded;
            }
        }

        $this->merge(['payload' => $payload]);
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
            'payload.project.next_action' => ['nullable', 'string'],
            'payload.team' => ['nullable', 'array'],
            'payload.tasks' => ['nullable', 'array'],
            'payload.decisions' => ['nullable', 'array'],
            'payload.reminders' => ['nullable', 'array'],
            'summary' => ['nullable', 'string'],
        ];
    }
}
