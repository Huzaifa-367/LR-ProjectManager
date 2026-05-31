<?php

namespace App\Http\Requests\Organizations;

use App\Enums\MailProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationMailProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'provider' => ['sometimes', 'required', Rule::enum(MailProvider::class)],
            'from_name' => ['sometimes', 'required', 'string', 'max:255'],
            'from_address' => ['sometimes', 'required', 'email', 'max:255'],
            'reply_to_address' => ['nullable', 'email', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
            'config.host' => ['nullable', 'string', 'max:255'],
            'config.port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'config.encryption' => ['nullable', Rule::in(['tls', 'ssl', null])],
            'config.username' => ['nullable', 'string', 'max:255'],
            'config.password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
