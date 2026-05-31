<?php

namespace App\Http\Requests\CommandCentre;

use App\Enums\PriorityLevel;
use App\Enums\TaskStatus;
use App\Models\Organization;
use App\Models\Task;
use App\Rules\ProjectTaskAssigneesMustBeOnTeam;
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
        /** @var Organization $organization */
        $organization = $this->route('organization');

        /** @var Task $task */
        $task = $this->route('task');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'assignee_member_ids' => [
                'sometimes',
                'array',
                new ProjectTaskAssigneesMustBeOnTeam($organization, $task->project_id),
            ],
            'assignee_member_ids.*' => ['integer', 'exists:organization_members,id'],
            'priority' => ['nullable', Rule::enum(PriorityLevel::class)],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'deadline_date' => ['nullable', 'date'],
            'external_link' => ['nullable', 'url', 'max:2048'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
