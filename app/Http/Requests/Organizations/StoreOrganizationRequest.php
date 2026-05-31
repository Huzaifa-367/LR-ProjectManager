<?php

namespace App\Http\Requests\Organizations;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:organizations,slug'],
            'settings.timezone' => ['nullable', 'timezone'],
            'settings.focus_cap' => ['nullable', 'integer', 'min:1', 'max:10'],
            'settings.ai_enabled' => ['nullable', 'boolean'],
        ];
    }
}
