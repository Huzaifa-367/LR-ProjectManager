<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\DeliveryStatus;
use App\Enums\MailProvider;
use App\Mail\TestOrganizationMailProfileMail;
use App\Models\NotificationDelivery;
use App\Models\OrganizationMailProfile;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrganizationMailProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_smtp_mail_profile(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Mail Org',
        ]);

        $this->actingAs($user)
            ->post(route('organizations.mail-profiles.store', $organization), [
                'name' => 'TCM Alerts',
                'provider' => MailProvider::Smtp->value,
                'from_name' => 'TCM Alerts',
                'from_address' => 'alerts@example.com',
                'is_default' => true,
                'config' => [
                    'host' => 'smtp.example.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username' => 'alerts@example.com',
                    'password' => 'secret',
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_mail_profiles', [
            'organization_id' => $organization->id,
            'name' => 'TCM Alerts',
            'from_address' => 'alerts@example.com',
            'is_default' => true,
        ]);
    }

    public function test_mail_settings_page_loads_for_owner(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Mail Settings Org',
        ]);

        $this->actingAs($user)
            ->get(route('organizations.mail-profiles.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('organizations/settings/mail')
                ->has('profiles')
                ->has('mailLinkage'));
    }

    public function test_owner_can_set_default_mail_profile(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Default Mail Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $defaultProfile = OrganizationMailProfile::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Primary',
            'provider' => MailProvider::Smtp,
            'is_default' => true,
            'from_name' => 'Primary',
            'from_address' => 'primary@example.com',
            'config' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'primary@example.com',
                'password' => 'secret',
            ],
            'is_verified' => false,
            'is_active' => true,
            'created_by_member_id' => $member->id,
        ]);

        $secondaryProfile = OrganizationMailProfile::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Secondary',
            'provider' => MailProvider::Smtp,
            'is_default' => false,
            'from_name' => 'Secondary',
            'from_address' => 'secondary@example.com',
            'config' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'secondary@example.com',
                'password' => 'secret',
            ],
            'is_verified' => false,
            'is_active' => true,
            'created_by_member_id' => $member->id,
        ]);

        $this->actingAs($user)
            ->patch(route('organizations.mail-profiles.update', [$organization, $secondaryProfile]), [
                'is_default' => true,
            ])
            ->assertRedirect();

        $defaultProfile->refresh();
        $secondaryProfile->refresh();

        $this->assertFalse($defaultProfile->is_default);
        $this->assertTrue($secondaryProfile->is_default);
    }

    public function test_test_send_logs_delivery_row_and_marks_profile_verified(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'owner@example.com']);
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Test Send Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $profile = OrganizationMailProfile::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Test Profile',
            'provider' => MailProvider::Smtp,
            'is_default' => true,
            'from_name' => 'Test',
            'from_address' => 'test@example.com',
            'config' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'test@example.com',
                'password' => 'secret',
            ],
            'is_verified' => false,
            'is_active' => true,
            'created_by_member_id' => $member->id,
        ]);

        $this->actingAs($user)
            ->post(route('organizations.mail-profiles.test', [$organization, $profile]))
            ->assertRedirect();

        Mail::assertSent(TestOrganizationMailProfileMail::class, function (TestOrganizationMailProfileMail $mail) use ($user): bool {
            return $mail->hasTo($user->email);
        });

        $profile->refresh();
        $this->assertTrue($profile->is_verified);
        $this->assertNotNull($profile->last_tested_at);

        $this->assertDatabaseHas('notification_deliveries', [
            'organization_id' => $organization->id,
            'organization_mail_profile_id' => $profile->id,
            'recipient_email' => $user->email,
            'status' => DeliveryStatus::Sent->value,
            'event_type' => 'mail_profile_test',
        ]);

        $this->assertSame(
            1,
            NotificationDelivery::query()
                ->where('organization_mail_profile_id', $profile->id)
                ->where('status', DeliveryStatus::Sent)
                ->count(),
        );
    }
}
