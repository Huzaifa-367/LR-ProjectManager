<?php

namespace App\Http\Requests\CommandCentre;

use Illuminate\Foundation\Http\FormRequest;

class ReorderFocusPinsRequest extends FormRequest
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
            'focus_date' => ['sometimes', 'date'],
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'exists:member_daily_focus,id'],
        ];
    }
}
