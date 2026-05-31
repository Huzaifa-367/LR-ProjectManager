<?php

namespace App\Support;

use App\Models\AiSession;

final class OnboardingConversationComposer
{
    /**
     * @param  array<string, string>  $answers
     */
    public function compose(AiSession $session, string $brief, array $answers = []): string
    {
        $segments = [];

        $accumulated = $session->messages()
            ->where('role', 'user')
            ->orderBy('id')
            ->pluck('content')
            ->filter(fn (?string $content): bool => trim((string) $content) !== '')
            ->all();

        foreach ($accumulated as $segment) {
            $segments[] = trim((string) $segment);
        }

        $normalizedBrief = trim(Utf8::sanitize($brief));

        if ($normalizedBrief !== '' && ! in_array($normalizedBrief, $segments, true)) {
            $segments[] = $normalizedBrief;
        }

        if ($answers !== []) {
            $segments[] = $this->formatAnswers($answers);
        }

        return trim(implode("\n\n", array_filter($segments)));
    }

    /**
     * @param  array<string, string>  $answers
     */
    public function formatAnswers(array $answers): string
    {
        $blocks = [];

        foreach ($answers as $key => $answer) {
            $normalizedAnswer = trim(Utf8::sanitize($answer));

            if ($normalizedAnswer === '') {
                continue;
            }

            $label = OnboardingRequirementRegistry::labelFor((string) $key);
            $blocks[] = $this->formatAnswerBlock($label, $normalizedAnswer);
        }

        return implode("\n\n", $blocks);
    }

    private function formatAnswerBlock(string $label, string $answer): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $answer) ?: [];
        $formatted = ["{$label}:"];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if ($this->looksLikeListItem($trimmed)) {
                $formatted[] = $trimmed;

                continue;
            }

            $formatted[] = '- '.$trimmed;
        }

        return implode("\n", $formatted);
    }

    private function looksLikeListItem(string $line): bool
    {
        return preg_match('/^([-*•]|\d+[.)])\s+/', $line) === 1;
    }
}
