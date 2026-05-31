<?php

namespace App\Http\Requests\CommandCentre;

use App\Enums\DeadlineType;
use App\Enums\PriorityLevel;
use App\Enums\TaskKind;
use App\Enums\TaskStatus;
use App\Models\Organization;
use App\Rules\ProjectTaskAssigneesMustBeOnTeam;
use App\Rules\StoreTaskRequiresProjectAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'kind' => ['required', Rule::enum(TaskKind::class)],
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id',
                new StoreTaskRequiresProjectAccess($organization, $this),
            ],
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'assignee_member_ids' => [
                'array',
                new ProjectTaskAssigneesMustBeOnTeam($organization, (int) $this->input('project_id')),
            ],
            'assignee_member_ids.*' => ['integer', 'exists:organization_members,id'],
            'priority' => ['nullable', Rule::enum(PriorityLevel::class)],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'deadline_type' => ['nullable', Rule::enum(DeadlineType::class)],
            'deadline_date' => ['required_if:deadline_type,date', 'nullable', 'date'],
            'external_link' => ['nullable', 'url', 'max:2048'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
