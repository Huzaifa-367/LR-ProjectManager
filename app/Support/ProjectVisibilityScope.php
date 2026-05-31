<?php

namespace App\Support;

use App\Models\OrganizationMember;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

final class ProjectVisibilityScope
{
    public function __construct(
        private readonly EffectivePermissionService $permissions,
    ) {}

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function apply(Builder $query, OrganizationMember $member): Builder
    {
        $query->where('organization_id', $member->organization_id);

        if ($this->permissions->hasOrgPermission($member, 'org.projects.scope.all')) {
            return $query;
        }

        abort_unless(
            $this->permissions->hasOrgPermission($member, 'org.projects.scope.member'),
            403,
        );

        return $query->whereHas('members', function (Builder $members) use ($member): void {
            $members->where('organization_member_id', $member->id);
        });
    }
}
