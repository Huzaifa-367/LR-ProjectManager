<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberInvitedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public OrganizationInvitation $invitation,
        public OrganizationMember $inviter,
        public string $acceptUrl,
    ) {
        $this->invitation->loadMissing('role');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Invitation to join :organization', [
                'organization' => $this->organization->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.member-invited',
            with: [
                'organizationName' => $this->organization->name,
                'inviterName' => $this->inviter->display_name,
                'roleName' => $this->invitation->role->name,
                'acceptUrl' => $this->acceptUrl,
                'expiresAt' => $this->invitation->expires_at->format('M j, Y'),
            ],
        );
    }
}
