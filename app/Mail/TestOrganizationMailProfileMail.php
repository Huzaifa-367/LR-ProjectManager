<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\OrganizationMailProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestOrganizationMailProfileMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
        public OrganizationMailProfile $profile,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->profile->from_address, $this->profile->from_name),
            replyTo: $this->profile->reply_to_address
                ? [new Address($this->profile->reply_to_address)]
                : [],
            subject: __('Test email from :organization', ['organization' => $this->organization->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.test-organization-mail-profile',
        );
    }
}
