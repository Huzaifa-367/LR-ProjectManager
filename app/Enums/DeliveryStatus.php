<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Bounced = 'bounced';
    case Suppressed = 'suppressed';
}
