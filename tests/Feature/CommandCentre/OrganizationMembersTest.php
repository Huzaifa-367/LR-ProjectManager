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

    public function test_adding_member_with_email_links_existing_user_account(): void
    {
        $owner = User::factory()->create();
        $memberUser = User::factory()->create(['email' => 'member@example.com']);
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Linked Org',
        ]);

        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('organizations.members.store', $organization), [
                'display_name' => 'Member Name',
                'email' => 'member@example.com',
                'organization_role_id' => $memberRole->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'email' => 'member@example.com',
            'user_id' => $memberUser->id,
            'status' => 'active',
        ]);
    }

    public function test_roster_member_sees_organization_after_login_when_email_matches(): void
    {
        $owner = User::factory()->create();
        $memberUser = User::factory()->create(['email' => 'linked@example.com']);
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Deferred Link Org',
        ]);

        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('organizations.members.store', $organization), [
                'display_name' => 'Linked Member',
                'email' => 'linked@example.com',
                'organization_role_id' => $memberRole->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'email' => 'linked@example.com',
            'user_id' => $memberUser->id,
        ]);

        $this->actingAs($memberUser)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('organizations/index')
                ->where('organizations.0.id', $organization->id)
                ->where('organizations.0.name', 'Deferred Link Org'));
    }

    public function test_pending_roster_membership_links_when_user_logs_in_later(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Late Link Org',
        ]);

        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('organizations.members.store', $organization), [
                'display_name' => 'Future Member',
                'email' => 'future@example.com',
                'organization_role_id' => $memberRole->id,
            ])
            ->assertRedirect();

        $memberUser = User::factory()->create(['email' => 'future@example.com']);

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'email' => 'future@example.com',
            'user_id' => null,
        ]);

        $this->actingAs($memberUser)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('organizations.0.id', $organization->id));

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'email' => 'future@example.com',
            'user_id' => $memberUser->id,
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
