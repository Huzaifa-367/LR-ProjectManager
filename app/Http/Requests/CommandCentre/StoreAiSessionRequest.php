<?php

namespace App\Http\Requests\CommandCentre;

use App\Enums\AiSessionContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiSessionRequest extends FormRequest
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
            'context' => ['required', Rule::enum(AiSessionContext::class)],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
