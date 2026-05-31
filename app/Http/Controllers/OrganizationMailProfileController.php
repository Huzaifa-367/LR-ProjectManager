<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Enums\NotificationChannel;
use App\Http\Requests\Organizations\StoreOrganizationMailProfileRequest;
use App\Http\Requests\Organizations\UpdateOrganizationMailProfileRequest;
use App\Mail\TestOrganizationMailProfileMail;
use App\Models\MemberMailLinkage;
use App\Models\NotificationDelivery;
use App\Models\Organization;
use App\Models\OrganizationMailProfile;
use App\Support\ActivityLogger;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMailerResolver;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OrganizationMailProfileController extends Controller
{
    public function index(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless(
            $permissions->memberCan($member, 'org.mail-profiles.index')
            || $permissions->memberCan($member, 'org.member-mail-linkage.show'),
            403,
        );

        $profiles = $permissions->memberCan($member, 'org.mail-profiles.index')
            ? OrganizationMailProfile::query()
                ->where('organization_id', $organization->id)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
                ->map(fn (OrganizationMailProfile $profile): array => $this->presentProfile($profile))
                ->values()
                ->all()
            : [];

        $mailLinkage = MemberMailLinkage::query()
            ->where('organization_member_id', $member->id)
            ->first();

        return Inertia::render('organizations/settings/mail', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'profiles' => $profiles,
            'mailLinkage' => [
                'gmail_address' => $mailLinkage?->gmail_address,
                'is_verified' => $mailLinkage?->is_verified ?? false,
                'last_tested_at' => $mailLinkage?->last_tested_at?->toIso8601String(),
                'has_app_pin' => $mailLinkage !== null,
            ],
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function store(
        StoreOrganizationMailProfileRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.mail-profiles.store'), 403);

        $validated = $request->validated();

        DB::transaction(function () use ($organization, $member, $validated): void {
            if ($validated['is_default']) {
                OrganizationMailProfile::query()
                    ->where('organization_id', $organization->id)
                    ->update(['is_default' => false]);
            }

            OrganizationMailProfile::query()->create([
                'organization_id' => $organization->id,
                'name' => $validated['name'],
                'provider' => $validated['provider'],
                'is_default' => $validated['is_default'],
                'from_name' => $validated['from_name'],
                'from_address' => $validated['from_address'],
                'reply_to_address' => $validated['reply_to_address'] ?? null,
                'config' => $validated['config'],
                'is_verified' => false,
                'is_active' => $validated['is_active'],
                'created_by_member_id' => $member->id,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mail profile created.')]);

        return back();
    }

    public function update(
        UpdateOrganizationMailProfileRequest $request,
        Organization $organization,
        OrganizationMailProfile $organizationMailProfile,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($organizationMailProfile->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.mail-profiles.update'), 403);

        $validated = $request->validated();

        if (isset($validated['config']['password']) && $validated['config']['password'] === '') {
            unset($validated['config']['password']);
        }

        if (isset($validated['config'])) {
            $validated['config'] = array_merge(
                $organizationMailProfile->config ?? [],
                $validated['config'],
            );
        }

        DB::transaction(function () use ($organization, $organizationMailProfile, $validated): void {
            if (($validated['is_default'] ?? false) === true) {
                OrganizationMailProfile::query()
                    ->where('organization_id', $organization->id)
                    ->whereKeyNot($organizationMailProfile->id)
                    ->update(['is_default' => false]);
            }

            $organizationMailProfile->update($validated);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mail profile updated.')]);

        return back();
    }

    public function destroy(
        Organization $organization,
        OrganizationMailProfile $organizationMailProfile,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($organizationMailProfile->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.mail-profiles.destroy'), 403);

        $organizationMailProfile->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mail profile deleted.')]);

        return back();
    }

    public function test(
        Organization $organization,
        OrganizationMailProfile $organizationMailProfile,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        OrganizationMailerResolver $mailerResolver,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);
        $user = request()->user();

        abort_unless($organizationMailProfile->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.mail-profiles.test'), 403);
        abort_if($user === null || $user->email === null, 422);

        $delivery = NotificationDelivery::query()->create([
            'organization_id' => $organization->id,
            'organization_mail_profile_id' => $organizationMailProfile->id,
            'organization_member_id' => $member->id,
            'recipient_user_id' => $user->id,
            'recipient_email' => $user->email,
            'channel' => NotificationChannel::Mail,
            'notification_class' => TestOrganizationMailProfileMail::class,
            'event_type' => 'mail_profile_test',
            'subject' => __('Test email from :organization', ['organization' => $organization->name]),
            'payload' => [
                'mail_profile_id' => $organizationMailProfile->id,
                'mail_profile_name' => $organizationMailProfile->name,
            ],
            'subject_type' => OrganizationMailProfile::class,
            'subject_id' => $organizationMailProfile->id,
            'status' => DeliveryStatus::Queued,
            'attempts' => 1,
            'queued_at' => now(),
        ]);

        try {
            $mailerName = $mailerResolver->registerMailer($organizationMailProfile);

            Mail::mailer($mailerName)
                ->to($user->email)
                ->send(new TestOrganizationMailProfileMail($organization, $organizationMailProfile));

            $organizationMailProfile->update([
                'is_verified' => true,
                'last_tested_at' => now(),
            ]);

            $delivery->update([
                'status' => DeliveryStatus::Sent,
                'sent_at' => now(),
            ]);

            app(ActivityLogger::class)->log(
                $organization->id,
                $member->id,
                $organizationMailProfile,
                'mail_profile.test_sent',
                ['delivery_id' => $delivery->id],
            );

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Test email sent to :email.', ['email' => $user->email]),
            ]);
        } catch (Throwable $exception) {
            $delivery->update([
                'status' => DeliveryStatus::Failed,
                'error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Test email failed: :error', ['error' => $exception->getMessage()]),
            ]);
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentProfile(OrganizationMailProfile $profile): array
    {
        $config = $profile->config ?? [];

        if (isset($config['password'])) {
            $config['password'] = '********';
        }

        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'provider' => $profile->provider->value,
            'is_default' => $profile->is_default,
            'from_name' => $profile->from_name,
            'from_address' => $profile->from_address,
            'reply_to_address' => $profile->reply_to_address,
            'config' => $config,
            'is_verified' => $profile->is_verified,
            'last_tested_at' => $profile->last_tested_at?->toIso8601String(),
            'is_active' => $profile->is_active,
        ];
    }
}
