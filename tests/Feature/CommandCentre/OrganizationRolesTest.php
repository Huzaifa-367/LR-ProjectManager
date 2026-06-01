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

    public function test_owner_can_create_update_and_delete_custom_organization_role(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Custom Roles Org',
        ]);

        $this->actingAs($owner)
            ->post(route('organizations.roles.store', $organization), [
                'name' => 'Delivery lead',
                'description' => 'Runs delivery workstreams',
                'permissions' => [
                    'org.command-centre.index',
                    'org.projects.index',
                    'org.tasks.index',
                ],
            ])
            ->assertRedirect();

        $customRole = $organization->roles()->where('slug', 'delivery-lead')->firstOrFail();

        $this->assertFalse($customRole->is_system);
        $this->assertContains('org.tasks.index', $customRole->permissionSlugs());

        $this->actingAs($owner)
            ->patch(route('organizations.roles.update', [$organization, $customRole]), [
                'name' => 'Delivery director',
                'description' => 'Updated description',
            ])
            ->assertRedirect();

        $customRole->refresh();

        $this->assertSame('Delivery director', $customRole->name);
        $this->assertSame('Updated description', $customRole->description);

        $this->actingAs($owner)
            ->delete(route('organizations.roles.destroy', [$organization, $customRole]))
            ->assertRedirect(route('organizations.roles.index', $organization));

        $this->assertDatabaseMissing('organization_roles', [
            'id' => $customRole->id,
        ]);
    }

    public function test_system_organization_role_cannot_be_deleted(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'System Role Org',
        ]);

        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('organizations.roles.destroy', [$organization, $memberRole]))
            ->assertForbidden();
    }

    public function test_system_organization_role_name_cannot_be_renamed(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Rename Org',
        ]);

        $adminRole = $organization->roles()->where('slug', 'admin')->firstOrFail();

        $this->actingAs($owner)
            ->patch(route('organizations.roles.update', [$organization, $adminRole]), [
                'description' => 'Org administrators',
            ])
            ->assertRedirect();

        $adminRole->refresh();

        $this->assertSame('Admin', $adminRole->name);
        $this->assertSame('Org administrators', $adminRole->description);
    }
}
