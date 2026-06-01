<?php

namespace App\Http\Requests\CommandCentre;

use App\Models\Project;
use App\Models\ProjectRole;
use App\Support\ProjectRoleSlugGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRoleRequest extends FormRequest
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
        /** @var Project $project */
        $project = $this->route('project');

        /** @var ProjectRole $projectRole */
        $projectRole = $this->route('projectRole');

        if (ProjectRoleSlugGenerator::isSystemSlug($projectRole->slug)) {
            return [];
        }

        return [
            'name' => [
                'required',
                'string',
                'max:64',
                Rule::unique('project_roles', 'name')
                    ->where('project_id', $project->id)
                    ->ignore($projectRole->id),
            ],
        ];
    }
}
