<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your :app account is ready', [
                'app' => config('app.name'),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.user-account-created',
            with: [
                'userName' => $this->user->name,
                'email' => $this->user->email,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => $this->loginUrl,
                'appName' => config('app.name'),
            ],
        );
    }
}
