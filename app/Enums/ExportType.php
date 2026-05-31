<?php

namespace App\Enums;

enum ExportType: string
{
    case TasksCsv = 'tasks_csv';
    case ProjectsCsv = 'projects_csv';
    case ActivityCsv = 'activity_csv';
}
