<?php

namespace Tests\Feature\CommandCentre;

use App\Models\OrganizationMember;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_roster_member_without_user_id(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Roster Org',
        ]);

        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('organizations.members.store', $organization), [
                'display_name' => 'Alex Roster',
                'email' => 'alex@example.com',
                'title' => 'Operations',
                'organization_role_id' => $memberRole->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'display_name' => 'Alex Roster',
            'email' => 'alex@example.com',
            'user_id' => null,
            'status' => 'active',
        ]);
    }

    public function test_disabled_member_cannot_access_tenant_routes(): void
    {
        $owner = User::factory()->create();
        $memberUser = User::factory()->create();

        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Disable Org',
        ]);

        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $member = OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
            'organization_role_id' => $memberRole->id,
            'display_name' => $memberUser->name,
            'email' => $memberUser->email,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($owner)
            ->patch(route('organizations.members.disable', [$organization, $member]))
            ->assertRedirect();

        $this->assertDatabaseHas('organization_members', [
            'id' => $member->id,
            'status' => 'disabled',
        ]);

        $this->actingAs($memberUser)
            ->get(route('organizations.projects.index', $organization))
            ->assertForbidden();
    }
}
