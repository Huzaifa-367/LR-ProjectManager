<?php

namespace App\Support;

use App\Enums\DeliveryStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\NotificationDelivery;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class OrganizationMailDeliveryService
{
    public function __construct(
        private readonly OrganizationMailerResolver $mailerResolver,
    ) {}

    public function send(
        Organization $organization,
        OrganizationMember $recipient,
        NotificationEventType $eventType,
        Model $subject,
        Mailable $mailable,
        string $mailSubject,
    ): ?NotificationDelivery {
        if ($recipient->user_id === null) {
            return null;
        }

        $user = User::query()->find($recipient->user_id);
        $email = $recipient->email ?? $user?->email;

        if ($email === null || $email === '') {
            return null;
        }

        $profile = $this->mailerResolver->defaultForOrganization($organization);

        $delivery = NotificationDelivery::query()->create([
            'organization_id' => $organization->id,
            'organization_mail_profile_id' => $profile?->id,
            'organization_member_id' => $recipient->id,
            'recipient_user_id' => $recipient->user_id,
            'recipient_email' => $email,
            'channel' => NotificationChannel::Mail,
            'notification_class' => $mailable::class,
            'event_type' => $eventType->value,
            'subject' => $mailSubject,
            'payload' => [
                'event_type' => $eventType->value,
            ],
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'status' => DeliveryStatus::Queued,
            'attempts' => 1,
            'queued_at' => now(),
        ]);

        try {
            if ($profile !== null) {
                $mailerName = $this->mailerResolver->registerMailer($profile);

                Mail::mailer($mailerName)
                    ->to($email)
                    ->send($mailable);
            } else {
                Mail::to($email)->send($mailable);
            }

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
        }

        return $delivery;
    }
}
