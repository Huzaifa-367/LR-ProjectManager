<?php

namespace App\Http\Requests\Organizations;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('organizations', 'slug')->ignore($organization->id),
            ],
            'settings.timezone' => ['nullable', 'timezone'],
            'settings.focus_cap' => ['nullable', 'integer', 'min:1', 'max:10'],
            'settings.ai_enabled' => ['nullable', 'boolean'],
        ];
    }
}
