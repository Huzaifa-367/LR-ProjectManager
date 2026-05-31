<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\DeliveryStatus;
use App\Enums\InvitationStatus;
use App\Mail\MemberInvitedMail;
use App\Models\MemberMailLinkage;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrganizationInvitationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_gmail_linkage_test_send_marks_linkage_verified(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Linkage Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        MemberMailLinkage::query()->create([
            'organization_member_id' => $ownerMember->id,
            'gmail_address' => 'owner@gmail.com',
            'app_password' => 'abcdefghijklmnop',
            'is_verified' => false,
        ]);

        $this->actingAs($owner)
            ->post(route('organizations.member-mail-linkage.test', $organization))
            ->assertRedirect();

        Mail::assertSent(\App\Mail\TestPersonalMailLinkageMail::class);

        $this->assertTrue(
            MemberMailLinkage::query()
                ->where('organization_member_id', $ownerMember->id)
                ->value('is_verified'),
        );
    }

    public function test_invitation_store_sends_member_invited_mail_via_personal_linkage(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Invite Mail Org',
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

        Mail::assertSent(MemberInvitedMail::class, function (MemberInvitedMail $mail): bool {
            return $mail->hasTo('newmember@example.com');
        });

        $this->assertDatabaseHas('notification_deliveries', [
            'organization_id' => $organization->id,
            'recipient_email' => 'newmember@example.com',
            'event_type' => 'member_invited',
            'status' => DeliveryStatus::Sent->value,
        ]);
    }

    public function test_invitation_store_requires_verified_personal_gmail_linkage(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'No Linkage Org',
        ]);
        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('organizations.invitations.store', $organization), [
                'email' => 'newmember@example.com',
                'organization_role_id' => $memberRole->id,
            ])
            ->assertSessionHasErrors('mail_linkage');
    }

    public function test_invitation_resend_rotates_token_and_sends_mail(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Resend Org',
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

        $originalHash = hash('sha256', 'original-token');

        $invitation = OrganizationInvitation::query()->create([
            'organization_id' => $organization->id,
            'email' => 'resend@example.com',
            'organization_role_id' => $memberRole->id,
            'invited_by_member_id' => $ownerMember->id,
            'token_hash' => $originalHash,
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($owner)
            ->post(route('organizations.invitations.resend', [$organization, $invitation]))
            ->assertRedirect();

        Mail::assertSent(MemberInvitedMail::class);

        $invitation->refresh();
        $this->assertNotSame($originalHash, $invitation->token_hash);
    }
}
