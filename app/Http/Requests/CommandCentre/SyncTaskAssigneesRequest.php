<?php

namespace App\Http\Requests\CommandCentre;

use App\Models\Organization;
use App\Models\Task;
use App\Rules\ProjectTaskAssigneesMustBeOnTeam;
use Illuminate\Foundation\Http\FormRequest;

class SyncTaskAssigneesRequest extends FormRequest
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
            'assignee_member_ids' => [
                'present',
                'array',
                new ProjectTaskAssigneesMustBeOnTeam($organization, $task->project_id),
            ],
            'assignee_member_ids.*' => ['integer', 'exists:organization_members,id'],
        ];
    }
}
