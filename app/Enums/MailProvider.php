<?php

namespace App\Enums;

enum MailProvider: string
{
    case Smtp = 'smtp';
    case GmailOauth = 'gmail_oauth';
    case MicrosoftOauth = 'microsoft_oauth';
}
