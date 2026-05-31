<?php

namespace App\Http\Requests\CommandCentre;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:25600',
            ],
            'attachable_type' => ['required', 'string', 'in:task'],
            'attachable_id' => ['required', 'integer'],
        ];
    }
}
