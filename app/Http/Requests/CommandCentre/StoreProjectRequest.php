<?php

namespace App\Http\Requests\CommandCentre;

use App\Enums\ProjectHealth;
use App\Models\Organization;
use App\Rules\OrganizationMemberBelongsToOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
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
        /** @var Organization $organization */
        $organization = $this->route('organization');

        return [
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'next_action' => ['nullable', 'string', 'max:500'],
            'health' => ['nullable', Rule::enum(ProjectHealth::class)],
            'team' => ['sometimes', 'array'],
            'team.*.organization_member_id' => [
                'required',
                'integer',
                'exists:organization_members,id',
                new OrganizationMemberBelongsToOrganization($organization),
            ],
            'team.*.project_role_slug' => [
                'nullable',
                'string',
                Rule::in(['project_owner', 'project_lead', 'contributor', 'project_viewer']),
            ],
        ];
    }
}
