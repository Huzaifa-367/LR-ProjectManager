<?php

namespace App\Concerns;

use App\Models\Project;
use App\Support\CommandCentrePermissionRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProjectRoleValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function projectRoleRules(Project $project, ?int $roleId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:64',
                $roleId === null
                    ? Rule::unique('project_roles', 'name')
                        ->where('project_id', $project->id)
                    : Rule::unique('project_roles', 'name')
                        ->where('project_id', $project->id)
                        ->ignore($roleId),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'string',
                Rule::in(CommandCentrePermissionRegistry::allProjectSlugs()),
            ],
        ];
    }
}
