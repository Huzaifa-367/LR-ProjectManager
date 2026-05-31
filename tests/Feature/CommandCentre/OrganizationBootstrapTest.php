<?php

namespace Tests\Feature\CommandCentre;

use App\Models\User;
use App\Support\CommandCentrePermissionRegistry;
use App\Support\CommandCentreRoleTemplateRegistry;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_create_materializes_five_roles_with_permissions(): void
    {
        $user = User::factory()->create();

        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'TCM Group',
        ]);

        $this->assertDatabaseCount('organization_roles', 5);
        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $ownerRole = $organization->roles()->where('slug', 'owner')->firstOrFail();
        $this->assertCount(
            count(CommandCentrePermissionRegistry::allOrgSlugs()),
            $ownerRole->permissions,
        );
    }

    public function test_owner_member_has_all_org_permissions(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Permission Test Org',
        ]);

        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $permissions = app(EffectivePermissionService::class)->orgPermissionsForMember($member);

        $this->assertTrue(
            app(EffectivePermissionService::class)->memberCan($member, 'org.tasks.store'),
        );
        $this->assertContains('org.organizations.destroy', $permissions);
    }

    public function test_viewer_role_template_excludes_task_mutations(): void
    {
        $viewer = collect(CommandCentreRoleTemplateRegistry::orgRoles())
            ->firstWhere('slug', 'viewer');

        $this->assertNotNull($viewer);
        $this->assertNotContains('org.tasks.store', $viewer['permissions']);
        $this->assertContains('org.command-centre.index', $viewer['permissions']);
    }
}
