<?php

namespace App\Support;

use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;

final class EffectivePermissionService
{
    /** @var array<int, list<string>> */
    private array $orgPermissionCache = [];

    /** @var array<string, list<string>> */
    private array $projectPermissionCache = [];

    public function orgPermissionsForMember(OrganizationMember $member): array
    {
        if (isset($this->orgPermissionCache[$member->id])) {
            return $this->orgPermissionCache[$member->id];
        }

        $member->loadMissing('role.permissions');

        $permissions = $member->role?->permissionSlugs() ?? [];

        $this->orgPermissionCache[$member->id] = $permissions;

        return $permissions;
    }

    public function projectPermissionsForMember(OrganizationMember $member, Project $project): array
    {
        $cacheKey = $member->id.':'.$project->id;

        if (isset($this->projectPermissionCache[$cacheKey])) {
            return $this->projectPermissionCache[$cacheKey];
        }

        $permissions = $this->projectPermissionsForMemberOnProjects($member, [$project])[$project->id] ?? [];

        return $permissions;
    }

    /**
     * @param  iterable<int, Project>  $projects
     * @return array<int, list<string>>
     */
    public function projectPermissionsForMemberOnProjects(OrganizationMember $member, iterable $projects): array
    {
        $projectIds = [];

        foreach ($projects as $project) {
            $projectIds[] = $project->id;
        }

        if ($projectIds === []) {
            return [];
        }

        $projectMembers = ProjectMember::query()
            ->where('organization_member_id', $member->id)
            ->whereIn('project_id', $projectIds)
            ->with('role.permissions')
            ->get()
            ->keyBy('project_id');

        $result = [];

        foreach ($projectIds as $projectId) {
            $permissions = $projectMembers->get($projectId)?->role?->permissionSlugs() ?? [];
            $this->projectPermissionCache[$member->id.':'.$projectId] = $permissions;
            $result[$projectId] = $permissions;
        }

        return $result;
    }

    /**
     * @return array{org: list<string>, projects: array<int, list<string>>}
     */
    public function sharedPermissionsForMember(OrganizationMember $member, ?Project $project = null): array
    {
        $projects = [];

        if ($project !== null) {
            $projects[$project->id] = $this->projectPermissionsForMember($member, $project);
        }

        return [
            'org' => $this->orgPermissionsForMember($member),
            'projects' => $projects,
        ];
    }

    public function memberCan(OrganizationMember $member, string $permission): bool
    {
        return in_array($permission, $this->orgPermissionsForMember($member), true);
    }

    public function memberCanOnProject(OrganizationMember $member, Project $project, string $permission): bool
    {
        return in_array($permission, $this->projectPermissionsForMember($member, $project), true);
    }

    public function memberCanAny(OrganizationMember $member, string ...$permissions): bool
    {
        $memberPermissions = $this->orgPermissionsForMember($member);

        foreach ($permissions as $permission) {
            if (in_array($permission, $memberPermissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasOrgPermission(OrganizationMember $member, string $permission): bool
    {
        return $this->memberCan($member, $permission);
    }

    public function memberCanViewTask(OrganizationMember $member, Task $task): bool
    {
        return $this->memberCanOnTask($member, $task, 'org.tasks.show', 'project.tasks.show');
    }

    public function memberCanUpdateTask(OrganizationMember $member, Task $task): bool
    {
        return $this->memberCanOnTask($member, $task, 'org.tasks.update', 'project.tasks.update');
    }

    public function memberCanDeleteTask(OrganizationMember $member, Task $task): bool
    {
        return $this->memberCanOnTask($member, $task, 'org.tasks.destroy', 'project.tasks.destroy');
    }

    public function memberCanOnTask(
        OrganizationMember $member,
        Task $task,
        string $orgPermission,
        ?string $projectPermission = null,
    ): bool {
        abort_unless($task->organization_id === $member->organization_id, 403);

        $project = $task->relationLoaded('project')
            ? $task->project
            : $task->project()->firstOrFail();

        $hasRoutePermission = $this->memberCan($member, $orgPermission)
            || ($projectPermission !== null && $this->memberCanOnProject($member, $project, $projectPermission));

        if (! $hasRoutePermission) {
            return false;
        }

        return $this->memberPassesTaskScope($member, $task, $project);
    }

    private function memberPassesTaskScope(
        OrganizationMember $member,
        Task $task,
        Project $project,
    ): bool {
        if ($this->hasOrgPermission($member, 'org.tasks.scope.all')) {
            return true;
        }

        if ($this->memberCanOnProject($member, $project, 'project.tasks.scope.all')) {
            return true;
        }

        if (
            $this->hasOrgPermission($member, 'org.tasks.scope.own')
            || $this->memberCanOnProject($member, $project, 'project.tasks.scope.own')
        ) {
            if ($task->created_by_member_id === $member->id) {
                return true;
            }

            return $task->assignees()
                ->where('organization_members.id', $member->id)
                ->exists();
        }

        return false;
    }
}
