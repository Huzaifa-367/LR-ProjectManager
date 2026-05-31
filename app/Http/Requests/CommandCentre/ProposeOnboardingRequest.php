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
            'brief' => ['required', 'string', 'max:5000'],
            'team' => ['nullable', 'array'],
            'team.*.organization_member_id' => ['required', 'integer', 'exists:organization_members,id'],
            'team.*.project_role_slug' => ['required', 'string', 'max:100'],
            'team.*.display_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
