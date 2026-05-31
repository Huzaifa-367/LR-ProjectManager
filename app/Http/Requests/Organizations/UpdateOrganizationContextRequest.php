<?php

namespace App\Http\Requests\Organizations;

use App\Enums\OrganizationMemberStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationContextRequest extends FormRequest
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
            'organization_id' => [
                'required',
                'integer',
                Rule::exists('organizations', 'id'),
                Rule::exists('organization_members', 'organization_id')
                    ->where(fn ($query) => $query
                        ->where('user_id', $this->user()?->id)
                        ->where('status', OrganizationMemberStatus::Active->value)),
            ],
        ];
    }
}
