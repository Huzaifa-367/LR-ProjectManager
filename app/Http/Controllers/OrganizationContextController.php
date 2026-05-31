<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organizations\UpdateOrganizationContextRequest;
use App\Models\Organization;
use App\Support\SelectedOrganizationManager;
use Illuminate\Http\RedirectResponse;

class OrganizationContextController extends Controller
{
    public function update(
        UpdateOrganizationContextRequest $request,
        SelectedOrganizationManager $selectedOrganizationManager,
    ): RedirectResponse {
        $organizationId = (int) $request->validated('organization_id');

        $selectedOrganizationManager->setSelectedOrganizationId($request, $organizationId);

        $organization = Organization::query()->findOrFail($organizationId);

        return to_route('organizations.command-centre.index', $organization);
    }
}
