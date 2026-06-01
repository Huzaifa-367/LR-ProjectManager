<?php

namespace App\Concerns;

use App\Models\Organization;
use App\Support\CommandCentrePermissionRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait OrganizationRoleValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function organizationRoleRules(
        Organization $organization,
        ?int $roleId = null,
    ): array {
        return [
            'name' => [
                'required',
                'string',
                'max:64',
                $roleId === null
                    ? Rule::unique('organization_roles', 'name')
                        ->where('organization_id', $organization->id)
                    : Rule::unique('organization_roles', 'name')
                        ->where('organization_id', $organization->id)
                        ->ignore($roleId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'string',
                Rule::in(CommandCentrePermissionRegistry::allOrgSlugs()),
            ],
        ];
    }
}
