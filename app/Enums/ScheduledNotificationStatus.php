<?php

namespace App\Enums;

enum ScheduledNotificationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Sent = 'sent';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}
