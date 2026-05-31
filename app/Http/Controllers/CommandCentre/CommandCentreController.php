<?php

namespace App\Http\Controllers\CommandCentre;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\CommandCentrePageBuilder;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Inertia\Inertia;
use Inertia\Response;

class CommandCentreController extends Controller
{
    public function index(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        CommandCentrePageBuilder $pageBuilder,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.command-centre.index'), 403);

        return Inertia::render('command-centre/index', $pageBuilder->build(
            $organization,
            $member,
            request(),
        ));
    }
}
