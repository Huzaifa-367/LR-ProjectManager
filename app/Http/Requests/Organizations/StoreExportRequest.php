<?php

namespace App\Http\Requests\Organizations;

use App\Enums\ExportType;
use App\Enums\TaskKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExportRequest extends FormRequest
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
            'export_type' => ['required', Rule::enum(ExportType::class)],
            'filters' => ['nullable', 'array'],
            'filters.project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'filters.kind' => ['nullable', Rule::enum(TaskKind::class)],
        ];
    }
}
