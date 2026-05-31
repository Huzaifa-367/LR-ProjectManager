<?php

namespace App\Mail;

use App\Models\MemberMailLinkage;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestPersonalMailLinkageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public MemberMailLinkage $linkage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Test email from your Gmail linkage'),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.personal-mail-linkage-test',
            with: [
                'organizationName' => $this->organization->name,
                'gmailAddress' => $this->linkage->gmail_address,
            ],
        );
    }
}
