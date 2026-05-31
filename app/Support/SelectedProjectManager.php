<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class SelectedProjectManager
{
    public const string SESSION_KEY = 'selected_project_by_organization';

    /**
     * @return Collection<int, Project>
     */
    public function accessibleProjects(Organization $organization, OrganizationMember $member): Collection
    {
        return Project::query()
            ->where('organization_id', $organization->id)
            ->active()
            ->tap(fn ($query) => app(ProjectVisibilityScope::class)->apply($query, $member))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function setSelectedProjectId(Request $request, int $organizationId, ?int $projectId): void
    {
        /** @var array<int, int> $map */
        $map = $request->session()->get(self::SESSION_KEY, []);

        if ($projectId === null) {
            unset($map[$organizationId]);
        } else {
            $map[$organizationId] = $projectId;
        }

        $request->session()->put(self::SESSION_KEY, $map);
    }

    public function resolveSelectedProjectId(
        Request $request,
        Organization $organization,
        OrganizationMember $member,
    ): ?int {
        /** @var array<int, int> $map */
        $map = $request->session()->get(self::SESSION_KEY, []);
        $projectId = $map[$organization->id] ?? null;

        if ($projectId === null) {
            return null;
        }

        $isVisible = Project::query()
            ->where('organization_id', $organization->id)
            ->whereKey($projectId)
            ->active()
            ->tap(fn ($query) => app(ProjectVisibilityScope::class)->apply($query, $member))
            ->exists();

        if (! $isVisible) {
            $this->setSelectedProjectId($request, $organization->id, null);

            return null;
        }

        return (int) $projectId;
    }

    public function resolveActiveProjectFilter(
        Request $request,
        Organization $organization,
        OrganizationMember $member,
    ): ?int {
        if ($request->filled('project_id')) {
            $projectId = $request->integer('project_id');

            $isVisible = Project::query()
                ->where('organization_id', $organization->id)
                ->whereKey($projectId)
                ->active()
                ->tap(fn ($query) => app(ProjectVisibilityScope::class)->apply($query, $member))
                ->exists();

            if ($isVisible) {
                $this->setSelectedProjectId($request, $organization->id, $projectId);

                return $projectId;
            }

            return null;
        }

        return $this->resolveSelectedProjectId($request, $organization, $member);
    }
}
