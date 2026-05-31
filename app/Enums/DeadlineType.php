<?php

namespace App\Enums;

enum DeadlineType: string
{
    case None = 'none';
    case Today = 'today';
    case ThisWeek = 'this_week';
    case Date = 'date';
}
