<?php

namespace App\Rules;

use App\Models\Organization;
use App\Models\Project;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use App\Support\ProjectVisibilityScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;

class StoreTaskRequiresProjectAccess implements ValidationRule
{
    public function __construct(
        private readonly Organization $organization,
        private readonly Request $request,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $project = Project::query()
            ->where('organization_id', $this->organization->id)
            ->whereKey($value)
            ->first();

        if ($project === null) {
            $fail(__('The selected project is invalid.'));

            return;
        }

        $user = $this->request->user();

        if ($user === null) {
            $fail(__('You must be signed in to create tasks.'));

            return;
        }

        $member = app(OrganizationMemberResolver::class)->requireForOrganization($user, $this->organization);
        $permissions = app(EffectivePermissionService::class);

        abort_unless($permissions->memberCan($member, 'org.tasks.store'), 403);

        $visible = Project::query()
            ->whereKey($project->id)
            ->tap(fn ($query) => app(ProjectVisibilityScope::class)->apply($query, $member))
            ->exists();

        if (! $visible) {
            $fail(__('You do not have access to this project.'));
        }
    }
}
