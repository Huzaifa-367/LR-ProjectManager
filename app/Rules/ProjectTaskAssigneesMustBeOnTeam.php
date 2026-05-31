<?php

namespace App\Rules;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProjectTaskAssigneesMustBeOnTeam implements ValidationRule
{
    public function __construct(
        private readonly Organization $organization,
        private readonly int $projectId,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || $value === []) {
            return;
        }

        $project = Project::query()
            ->where('organization_id', $this->organization->id)
            ->whereKey($this->projectId)
            ->first();

        if ($project === null) {
            $fail(__('The selected project is invalid.'));

            return;
        }

        $teamMemberIds = ProjectMember::query()
            ->where('project_id', $project->id)
            ->pluck('organization_member_id')
            ->all();

        foreach ($value as $memberId) {
            if (! in_array((int) $memberId, $teamMemberIds, true)) {
                $fail(__('All assignees must be members of the project team.'));

                return;
            }
        }
    }
}
