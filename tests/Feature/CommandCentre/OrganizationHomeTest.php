<?php

namespace Tests\Feature\CommandCentre;

use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\SelectedOrganizationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_organization_home(): void
    {
        $this->get(route('organizations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_new_user_sees_empty_organization_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('organizations/index')
                ->where('organizations', []));
    }

    public function test_user_can_create_organization_and_enter_command_centre(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('organizations.store'), [
                'name' => 'TCM Group',
                'settings' => [
                    'timezone' => 'Asia/Karachi',
                    'focus_cap' => 10,
                ],
            ])
            ->assertRedirect();

        $organization = Organization::query()->where('slug', 'tcm-group')->first();

        $this->assertNotNull($organization);
        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'active',
            'is_primary_org' => true,
        ]);
        $this->assertDatabaseCount('organization_roles', 5);

        $this->actingAs($user)
            ->get(route('organizations.command-centre.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('command-centre/index')
                ->where('organization.name', 'TCM Group')
                ->has('stats'));
    }

    public function test_organization_select_updates_session_and_redirects(): void
    {
        $user = User::factory()->create();
        $bootstrap = app(OrganizationBootstrapService::class);

        $first = $bootstrap->create($user, ['name' => 'Alpha Org']);
        $second = $bootstrap->create($user, ['name' => 'Beta Org']);

        $this->actingAs($user)
            ->post(route('organizations.select'), [
                'organization_id' => $second->id,
            ])
            ->assertRedirect(route('organizations.command-centre.index', $second));

        $this->assertSame(
            $second->id,
            session(SelectedOrganizationManager::SESSION_KEY),
        );

        $this->actingAs($user)
            ->post(route('organizations.select'), [
                'organization_id' => $first->id,
            ])
            ->assertRedirect(route('organizations.command-centre.index', $first));
    }

    public function test_user_cannot_select_organization_without_active_membership(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Private Org',
        ]);

        $this->actingAs($other)
            ->post(route('organizations.select'), [
                'organization_id' => $organization->id,
            ])
            ->assertSessionHasErrors('organization_id');
    }
}
