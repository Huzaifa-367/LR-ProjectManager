<?php

namespace App\Http\Requests\CommandCentre;

use App\Models\Organization;
use App\Models\Project;
use App\Rules\OrganizationMemberBelongsToOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectMemberRequest extends FormRequest
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

        /** @var Project $project */
        $project = $this->route('project');

        return [
            'organization_member_id' => [
                'required',
                'integer',
                'exists:organization_members,id',
                new OrganizationMemberBelongsToOrganization($organization),
            ],
            'project_role_id' => [
                'required',
                'integer',
                Rule::exists('project_roles', 'id')->where('project_id', $project->id),
            ],
        ];
    }
}
