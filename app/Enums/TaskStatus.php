<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Stuck = 'stuck';
    case Hold = 'hold';
    case FollowUp = 'follow_up';
}
