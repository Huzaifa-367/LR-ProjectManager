<?php

namespace App\Http\Requests\Organizations;

use App\Concerns\OrganizationRoleValidationRules;
use App\Models\Organization;
use App\Models\OrganizationRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRoleRequest extends FormRequest
{
    use OrganizationRoleValidationRules;

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

        /** @var OrganizationRole $organizationRole */
        $organizationRole = $this->route('organizationRole');

        if ($organizationRole->is_system) {
            return [
                'description' => ['nullable', 'string', 'max:500'],
            ];
        }

        return [
            'name' => [
                'required',
                'string',
                'max:64',
                Rule::unique('organization_roles', 'name')
                    ->where('organization_id', $organization->id)
                    ->ignore($organizationRole->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
