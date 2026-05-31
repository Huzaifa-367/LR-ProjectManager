<?php

namespace App\Http\Requests\CommandCentre;

use App\Enums\DeadlineType;
use App\Enums\PriorityLevel;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', Rule::enum(PriorityLevel::class)],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'deadline_type' => ['nullable', Rule::enum(DeadlineType::class)],
            'deadline_date' => ['required_if:deadline_type,date', 'nullable', 'date'],
            'external_link' => ['nullable', 'url', 'max:2048'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
