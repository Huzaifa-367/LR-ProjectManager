<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\InvitationStatus;
use App\Enums\OrganizationMemberStatus;
use App\Models\MemberMailLinkage;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationAcceptTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_accept_creates_active_member(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Invite Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();
        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $token = 'test-invitation-token';

        $invitation = OrganizationInvitation::query()->create([
            'organization_id' => $organization->id,
            'email' => 'invitee@example.com',
            'organization_role_id' => $memberRole->id,
            'invited_by_member_id' => $ownerMember->id,
            'token_hash' => hash('sha256', $token),
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($invitee)
            ->post(route('invitations.accept', ['token' => $token]))
            ->assertRedirect(route('organizations.command-centre.index', $organization));

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $invitee->id,
            'status' => OrganizationMemberStatus::Active->value,
        ]);

        $invitation->refresh();
        $this->assertSame(InvitationStatus::Accepted, $invitation->status);
        $this->assertNotNull($invitation->organization_member_id);
    }

    public function test_admin_can_create_invitation_when_gmail_linkage_verified(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Send Invite Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();
        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        MemberMailLinkage::query()->create([
            'organization_member_id' => $ownerMember->id,
            'gmail_address' => 'owner@gmail.com',
            'app_password' => 'abcdefghijklmnop',
            'is_verified' => true,
            'last_tested_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('organizations.invitations.store', $organization), [
                'email' => 'newmember@example.com',
                'organization_role_id' => $memberRole->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_invitations', [
            'organization_id' => $organization->id,
            'email' => 'newmember@example.com',
            'status' => InvitationStatus::Pending->value,
        ]);

        Mail::assertSent(\App\Mail\MemberInvitedMail::class);
    }

    public function test_pending_invitation_accept_route_works_for_matching_user(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Pending Accept Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();
        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();
        $invitee = User::factory()->create(['email' => 'pending@example.com']);

        $invitation = OrganizationInvitation::query()->create([
            'organization_id' => $organization->id,
            'email' => 'pending@example.com',
            'organization_role_id' => $memberRole->id,
            'invited_by_member_id' => $ownerMember->id,
            'token_hash' => hash('sha256', 'unused'),
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($invitee)
            ->post(route('invitations.accept-pending', $invitation))
            ->assertRedirect(route('organizations.command-centre.index', $organization));

        $this->assertTrue(
            OrganizationMember::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $invitee->id)
                ->where('status', OrganizationMemberStatus::Active)
                ->exists(),
        );
    }
}
