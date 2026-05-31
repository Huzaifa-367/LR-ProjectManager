<?php

namespace App\Enums;

enum TaskKind: string
{
    case Task = 'task';
    case Reminder = 'reminder';
    case Decision = 'decision';
}
