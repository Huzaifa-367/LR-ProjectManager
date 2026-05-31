<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organizations\UpdateProjectContextRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Support\OrganizationMemberResolver;
use App\Support\ProjectVisibilityScope;
use App\Support\SelectedProjectManager;
use Illuminate\Http\RedirectResponse;

class ProjectContextController extends Controller
{
    public function update(
        UpdateProjectContextRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        SelectedProjectManager $selectedProjectManager,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);
        $projectId = $request->selectedProjectId();

        if ($projectId !== null) {
            $isVisible = Project::query()
                ->where('organization_id', $organization->id)
                ->whereKey($projectId)
                ->active()
                ->tap(fn ($query) => app(ProjectVisibilityScope::class)->apply($query, $member))
                ->exists();

            abort_unless($isVisible, 422);
        }

        $selectedProjectManager->setSelectedProjectId($request, $organization->id, $projectId);

        return back();
    }
}
