<?php

namespace App\Support;

use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

final class TaskVisibilityScope
{
    public function __construct(
        private readonly EffectivePermissionService $permissions,
        private readonly ProjectVisibilityScope $projectVisibility,
    ) {}

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function apply(Builder $query, OrganizationMember $member): Builder
    {
        $query->where('organization_id', $member->organization_id);

        if ($this->permissions->hasOrgPermission($member, 'org.tasks.scope.all')) {
            return $this->applyProjectTaskScope($query, $member);
        }

        abort_unless(
            $this->permissions->hasOrgPermission($member, 'org.tasks.scope.own'),
            403,
        );

        $query->where(function (Builder $inner) use ($member): void {
            $inner->whereHas('assignees', function (Builder $assignees) use ($member): void {
                $assignees->where('organization_members.id', $member->id);
            })->orWhere('created_by_member_id', $member->id);
        });

        return $this->applyProjectTaskScope($query, $member);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    private function applyProjectTaskScope(Builder $query, OrganizationMember $member): Builder
    {
        $visibleProjectIds = Project::query()
            ->tap(fn (Builder $projects) => $this->projectVisibility->apply($projects, $member))
            ->pluck('id');

        return $query->whereIn('project_id', $visibleProjectIds);
    }
}
