<?php

namespace App\Enums;

enum AiSessionStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
}
