<?php

namespace Tests\Feature\CommandCentre;

use App\Models\User;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppShellOrganizationContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_centre_page_shares_organization_context_for_selector(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Shell Org',
        ]);

        $this->actingAs($user)
            ->get(route('organizations.command-centre.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('organizationContext.organizations', 1)
                ->where('organizationContext.selectedOrganization.id', $organization->id)
                ->has('organizationContext.projects')
                ->has('organizationContext.permissions'));
    }
}
