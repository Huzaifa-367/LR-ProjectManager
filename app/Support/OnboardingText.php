<?php

namespace App\Support;

final class OnboardingText
{
    public static function clipTitle(string $value, int $maxLength): string
    {
        return self::clipAtWord(self::cleanMarkdown(trim($value)), $maxLength);
    }

    public static function clipAtWord(string $value, int $maxLength): string
    {
        $trimmed = trim(Utf8::sanitize($value));

        if ($trimmed === '' || mb_strlen($trimmed) <= $maxLength) {
            return $trimmed;
        }

        $words = preg_split('/\s+/u', $trimmed) ?: [];
        $result = '';

        foreach ($words as $word) {
            $next = $result === '' ? $word : $result.' '.$word;

            if (mb_strlen($next) > $maxLength) {
                break;
            }

            $result = $next;
        }

        if ($result !== '') {
            return $result;
        }

        return mb_substr($trimmed, 0, $maxLength);
    }

    public static function cleanMarkdown(string $value): string
    {
        $clean = preg_replace('/\*\*(.+?)\*\*/u', '$1', $value) ?? $value;
        $clean = preg_replace('/`([^`]+)`/u', '$1', $clean) ?? $clean;

        return trim($clean);
    }

    public static function isIncompletePhrase(string $value): bool
    {
        return preg_match('/\b(is a|is an|will be|should be|are a)\s*$/iu', trim($value)) === 1;
    }
}
