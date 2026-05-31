<?php

namespace App\Support;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

final class TaskDeadlineValue
{
    public static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '' || in_array(strtolower($trimmed), ['null', 'none', 'n/a'], true)) {
            return null;
        }

        try {
            return Carbon::parse($trimmed)->toDateTimeString();
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
