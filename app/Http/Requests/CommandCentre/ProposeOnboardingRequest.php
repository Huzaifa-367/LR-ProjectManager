<?php

namespace App\Http\Requests\CommandCentre;

use Illuminate\Foundation\Http\FormRequest;

class ProposeOnboardingRequest extends FormRequest
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
            'ai_session_id' => ['required', 'integer', 'exists:ai_sessions,id'],
            'brief' => ['nullable', 'string', 'max:5000'],
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string', 'max:2000'],
            'team' => ['nullable', 'array'],
            'team.*.organization_member_id' => ['required', 'integer', 'exists:organization_members,id'],
            'team.*.project_role_slug' => ['required', 'string', 'max:100'],
            'team.*.display_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $brief = trim((string) $this->input('brief', ''));
            $answers = array_filter(
                (array) $this->input('answers', []),
                fn ($value): bool => trim((string) $value) !== '',
            );

            if ($brief === '' && $answers === []) {
                $validator->errors()->add('brief', __('Provide a project brief or answer the follow-up questions.'));
            }
        });
    }
}
