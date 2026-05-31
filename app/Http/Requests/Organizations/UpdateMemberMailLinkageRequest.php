<?php

namespace App\Http\Requests\Organizations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberMailLinkageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('app_pin')) {
            $this->merge([
                'app_pin' => preg_replace('/\s+/', '', (string) $this->input('app_pin')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gmail_address' => ['required', 'email', 'max:255'],
            'app_pin' => ['nullable', 'string', 'min:16', 'max:24'],
        ];
    }
}
