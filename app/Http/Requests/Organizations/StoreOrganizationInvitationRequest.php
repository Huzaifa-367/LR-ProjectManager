<?php

namespace App\Http\Requests\Organizations;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationInvitationRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'organization_role_id' => [
                'required',
                'integer',
                Rule::exists('organization_roles', 'id')
                    ->where('organization_id', $organization->id),
            ],
        ];
    }
}
