<?php

namespace App\Support;

final class Utf8
{
    public static function sanitize(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $clean = iconv('UTF-8', 'UTF-8//IGNORE', $value);

        if ($clean !== false) {
            return $clean;
        }

        $filtered = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        return is_string($filtered) ? $filtered : '';
    }

    public static function truncate(string $value, int $maxLength): string
    {
        $trimmed = trim(self::sanitize($value));

        if (mb_strlen($trimmed) <= $maxLength) {
            return $trimmed;
        }

        return mb_substr($trimmed, 0, $maxLength - 3).'...';
    }

    public static function sanitizeRecursive(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                $sanitizedKey = is_string($key) ? self::sanitize($key) : $key;
                $sanitized[$sanitizedKey] = self::sanitizeRecursive($item);
            }

            return $sanitized;
        }

        if (is_string($value)) {
            return self::sanitize($value);
        }

        return $value;
    }
}
