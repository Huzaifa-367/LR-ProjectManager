<?php

namespace Tests\Feature\CommandCentre;

use App\Models\User;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permissions_sync_accepts_only_valid_slugs(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Roles Org',
        ]);

        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $this->actingAs($owner)
            ->put(route('organizations.roles.permissions.sync', [$organization, $memberRole]), [
                'permissions' => [
                    'org.command-centre.index',
                    'org.tasks.index',
                    'org.tasks.scope.own',
                    'not.a.real.slug',
                ],
            ])
            ->assertSessionHasErrors('permissions.3');

        $this->actingAs($owner)
            ->put(route('organizations.roles.permissions.sync', [$organization, $memberRole]), [
                'permissions' => [
                    'org.command-centre.index',
                    'org.tasks.index',
                    'org.tasks.scope.own',
                ],
            ])
            ->assertRedirect();

        $memberRole->refresh();

        $this->assertContains('org.tasks.index', $memberRole->permissionSlugs());
        $this->assertNotContains('not.a.real.slug', $memberRole->permissionSlugs());
    }

    public function test_owner_role_sync_keeps_locked_destroy_permission(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Owner Role Org',
        ]);

        $ownerRole = $organization->roles()->where('slug', 'owner')->firstOrFail();

        $this->actingAs($owner)
            ->put(route('organizations.roles.permissions.sync', [$organization, $ownerRole]), [
                'permissions' => ['org.command-centre.index'],
            ])
            ->assertRedirect();

        $ownerRole->refresh();

        $this->assertContains('org.organizations.destroy', $ownerRole->permissionSlugs());
    }
}
