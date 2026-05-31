<?php

namespace App\Http\Requests\Organizations;

use App\Enums\MailProvider;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationMailProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', Rule::enum(MailProvider::class)],
            'from_name' => ['required', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'reply_to_address' => ['nullable', 'email', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['required', 'array'],
            'config.host' => ['required_if:provider,smtp', 'nullable', 'string', 'max:255'],
            'config.port' => ['required_if:provider,smtp', 'nullable', 'integer', 'min:1', 'max:65535'],
            'config.encryption' => ['nullable', Rule::in(['tls', 'ssl', null])],
            'config.username' => ['required_if:provider,smtp', 'nullable', 'string', 'max:255'],
            'config.password' => ['required_if:provider,smtp', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return is_array($validated) ? $validated : [];
        }

        $validated['is_default'] = (bool) ($validated['is_default'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        return $validated;
    }
}
