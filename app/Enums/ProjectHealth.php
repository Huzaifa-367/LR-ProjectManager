<?php

namespace App\Enums;

enum ProjectHealth: string
{
    case Active = 'active';
    case Progressing = 'progressing';
    case Steady = 'steady';
}
