<?php

namespace App\Http\Requests\Organizations;

use App\Concerns\OrganizationRoleValidationRules;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRoleRequest extends FormRequest
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

        return $this->organizationRoleRules($organization);
    }
}
