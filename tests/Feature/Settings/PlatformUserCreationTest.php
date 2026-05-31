<?php

namespace Tests\Feature\Settings;

use App\Mail\UserAccountCreatedMail;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformUserCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_platform_admin_can_create_user_and_send_login_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('platform_admin');

        $response = $this->actingAs($admin)->post(route('user-roles.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'platform_admin',
        ]);

        $response->assertRedirect();

        $user = User::query()->where('email', 'newuser@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('New User', $user->name);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue($user->hasRole('platform_admin'));

        Mail::assertSent(UserAccountCreatedMail::class, function (UserAccountCreatedMail $mail) use ($user): bool {
            return $mail->hasTo('newuser@example.com')
                && $mail->user->is($user)
                && Hash::check($mail->temporaryPassword, $user->fresh()->getAuthPassword());
        });
    }

    public function test_platform_user_creation_links_existing_roster_membership_by_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('platform_admin');

        $owner = User::factory()->create();
        $organization = app(\App\Support\OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Platform Link Org',
        ]);
        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('organizations.members.store', $organization), [
                'display_name' => 'Roster Only',
                'email' => 'roster-link@example.com',
                'organization_role_id' => $memberRole->id,
            ])
            ->assertRedirect();

        $this->actingAs($admin)->post(route('user-roles.store'), [
            'name' => 'Roster Only',
            'email' => 'roster-link@example.com',
            'role' => 'platform_admin',
        ])->assertRedirect();

        $user = User::query()->where('email', 'roster-link@example.com')->firstOrFail();

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'email' => 'roster-link@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_created_user_can_log_in_with_temporary_password_from_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('platform_admin');

        $this->actingAs($admin)->post(route('user-roles.store'), [
            'name' => 'Login User',
            'email' => 'loginuser@example.com',
            'role' => Role::query()->where('name', 'platform_admin')->value('name'),
        ])->assertRedirect();

        $temporaryPassword = null;

        Mail::assertSent(UserAccountCreatedMail::class, function (UserAccountCreatedMail $mail) use (&$temporaryPassword): bool {
            $temporaryPassword = $mail->temporaryPassword;

            return true;
        });

        $this->assertNotNull($temporaryPassword);

        auth()->logout();

        $this->post(route('login.store'), [
            'email' => 'loginuser@example.com',
            'password' => $temporaryPassword,
        ])->assertRedirect(route('organizations.index', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_user_without_manage_permission_cannot_create_users(): void
    {
        $viewer = User::factory()->create();
        $viewer->syncRoles([]);

        $this->actingAs($viewer)
            ->post(route('user-roles.store'), [
                'name' => 'Blocked User',
                'email' => 'blocked@example.com',
                'role' => 'platform_admin',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@example.com',
        ]);
    }

    public function test_store_validates_unique_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('platform_admin');

        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->post(route('user-roles.store'), [
                'name' => 'Duplicate User',
                'email' => 'taken@example.com',
                'role' => 'platform_admin',
            ])
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }
}
