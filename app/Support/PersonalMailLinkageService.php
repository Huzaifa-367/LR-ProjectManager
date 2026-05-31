<?php

namespace App\Support;

use App\Enums\DeliveryStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Mail\MemberInvitedMail;
use App\Mail\TestPersonalMailLinkageMail;
use App\Models\MemberMailLinkage;
use App\Models\NotificationDelivery;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

final class PersonalMailLinkageService
{
    public function requireVerifiedLinkage(OrganizationMember $member): MemberMailLinkage
    {
        $linkage = MemberMailLinkage::query()
            ->where('organization_member_id', $member->id)
            ->first();

        if ($linkage === null || ! $linkage->is_verified) {
            throw ValidationException::withMessages([
                'mail_linkage' => __('Connect and verify your personal Gmail linkage before sending invitation emails.'),
            ]);
        }

        return $linkage;
    }

    public function sendInvitation(
        Organization $organization,
        OrganizationMember $inviter,
        OrganizationInvitation $invitation,
        string $acceptUrl,
    ): NotificationDelivery {
        $linkage = $this->requireVerifiedLinkage($inviter);
        $invitation->loadMissing(['role', 'organization']);

        $mailable = new MemberInvitedMail(
            organization: $organization,
            invitation: $invitation,
            inviter: $inviter,
            acceptUrl: $acceptUrl,
        );

        return $this->send(
            organization: $organization,
            sender: $inviter,
            linkage: $linkage,
            recipientEmail: $invitation->email,
            eventType: NotificationEventType::MemberInvited,
            subject: $mailable->envelope()->subject,
            mailable: $mailable,
            subjectModel: $invitation,
            notificationClass: MemberInvitedMail::class,
        );
    }

    public function sendTest(
        Organization $organization,
        OrganizationMember $member,
        MemberMailLinkage $linkage,
        User $user,
    ): NotificationDelivery {
        $mailable = new TestPersonalMailLinkageMail($organization, $linkage);

        return $this->send(
            organization: $organization,
            sender: $member,
            linkage: $linkage,
            recipientEmail: $user->email,
            eventType: NotificationEventType::MemberInvited,
            subject: $mailable->envelope()->subject,
            mailable: $mailable,
            subjectModel: $linkage,
            notificationClass: TestPersonalMailLinkageMail::class,
            eventTypeOverride: 'mail_linkage_test',
        );
    }

    public function mailerName(MemberMailLinkage $linkage): string
    {
        return "member_mail_linkage_{$linkage->id}";
    }

    public function registerMailer(MemberMailLinkage $linkage): string
    {
        $mailerName = $this->mailerName($linkage);

        config([
            "mail.mailers.{$mailerName}" => [
                'transport' => 'smtp',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => $linkage->gmail_address,
                'password' => $linkage->app_password,
                'timeout' => null,
                'local_domain' => parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST),
            ],
        ]);

        return $mailerName;
    }

    private function send(
        Organization $organization,
        OrganizationMember $sender,
        MemberMailLinkage $linkage,
        string $recipientEmail,
        NotificationEventType $eventType,
        string $subject,
        Mailable $mailable,
        object $subjectModel,
        string $notificationClass,
        ?string $eventTypeOverride = null,
    ): NotificationDelivery {
        $delivery = NotificationDelivery::query()->create([
            'organization_id' => $organization->id,
            'organization_mail_profile_id' => null,
            'organization_member_id' => $sender->id,
            'recipient_user_id' => null,
            'recipient_email' => $recipientEmail,
            'channel' => NotificationChannel::Mail,
            'notification_class' => $notificationClass,
            'event_type' => $eventTypeOverride ?? $eventType->value,
            'subject' => $subject,
            'payload' => [
                'sender_gmail' => $linkage->gmail_address,
                'delivery_method' => 'personal_gmail_linkage',
            ],
            'subject_type' => $subjectModel->getMorphClass(),
            'subject_id' => $subjectModel->getKey(),
            'status' => DeliveryStatus::Queued,
            'attempts' => 1,
            'queued_at' => now(),
        ]);

        try {
            $mailerName = $this->registerMailer($linkage);

            Mail::mailer($mailerName)
                ->to($recipientEmail)
                ->send($mailable);

            $delivery->update([
                'status' => DeliveryStatus::Sent,
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $delivery->update([
                'status' => DeliveryStatus::Failed,
                'error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);

            throw $exception;
        }

        return $delivery;
    }
}
