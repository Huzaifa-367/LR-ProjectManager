<?php

namespace App\Http\Requests\CommandCentre;

use App\Support\CommandCentrePermissionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncProjectRolePermissionsRequest extends FormRequest
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
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', Rule::in(CommandCentrePermissionRegistry::allProjectSlugs())],
        ];
    }
}
