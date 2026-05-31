<?php

namespace App\Support;

final class OnboardingBriefParser
{
    private const int MaxProjectNameLength = 55;

    /**
     * @return array{
     *     project_name: string|null,
     *     objective: string|null,
     *     work_item_count: int,
     *     has_timeline: bool,
     *     has_success_criteria: bool,
     *     has_decisions: bool,
     *     has_reminders: bool,
     *     sections: array<string, list<string>>
     * }
     */
    public function extractStructure(string $brief): array
    {
        $normalizedBrief = trim(Utf8::sanitize($brief));

        if ($normalizedBrief === '') {
            return [
                'project_name' => null,
                'objective' => null,
                'work_item_count' => 0,
                'has_timeline' => false,
                'has_success_criteria' => false,
                'has_decisions' => false,
                'has_reminders' => false,
                'sections' => [],
            ];
        }

        $lines = preg_split('/\r\n|\r|\n/', $normalizedBrief) ?: [];
        $sections = $this->parseSections($lines);

        return [
            'project_name' => $this->resolveProjectName($lines, $sections, $normalizedBrief),
            'objective' => $this->resolveObjective($sections, $normalizedBrief),
            'work_item_count' => $this->countWorkItems($sections, $lines),
            'has_timeline' => $this->hasTimeline($sections, $normalizedBrief),
            'has_success_criteria' => $this->hasSuccessCriteria($sections),
            'has_decisions' => $this->hasDecisions($sections),
            'has_reminders' => $this->hasReminders($sections),
            'sections' => $sections,
        ];
    }

    /**
     * @param  array<string, list<string>>  $sections
     * @param  list<string>  $lines
     */
    private function countWorkItems(array $sections, array $lines): int
    {
        $keys = [
            'scope',
            'deliverables',
            'requirements',
            'tasks',
            'workstreams',
            'actions',
            'next_steps',
            'implementation',
            'rollout',
            'milestones',
            'phases',
            'curriculum',
            'modules',
            'agenda',
            'program',
            'activities',
        ];

        $count = 0;

        foreach ($keys as $key) {
            $count += count($sections[$key] ?? []);
        }

        if ($count > 0) {
            return $count;
        }

        foreach ($lines as $line) {
            if ($this->normalizeListItem(trim($line)) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, list<string>>  $sections
     */
    private function hasTimeline(array $sections, string $brief): bool
    {
        foreach (['timeline', 'milestones', 'phases', 'schedule'] as $key) {
            if (($sections[$key] ?? []) !== []) {
                return true;
            }
        }

        return preg_match(
            '/\b(q[1-4]|quarter|by\s+[a-z]+|\d{4}-\d{2}-\d{2}|deadline|within\s+\d+\s+(?:day|week|month)|end of (?:week|month|year)|eom|eoy)\b/i',
            $brief,
        ) === 1;
    }

    /**
     * @param  array<string, list<string>>  $sections
     */
    private function hasSuccessCriteria(array $sections): bool
    {
        foreach (['success_criteria', 'success', 'metrics', 'kpis'] as $key) {
            if (($sections[$key] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, list<string>>  $sections
     */
    private function hasDecisions(array $sections): bool
    {
        foreach (['decisions', 'decisions_needed', 'approvals', 'open_questions'] as $key) {
            if (($sections[$key] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, list<string>>  $sections
     */
    private function hasReminders(array $sections): bool
    {
        foreach (['reminders', 'check_ins', 'cadence', 'steerco'] as $key) {
            if (($sections[$key] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $lines
     * @return array<string, list<string>>
     */
    private function parseSections(array $lines): array
    {
        $sections = ['general' => []];
        $currentSection = 'general';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if ($this->isSectionHeader($trimmed)) {
                $currentSection = $this->normalizeSectionKey($trimmed);

                if (! isset($sections[$currentSection])) {
                    $sections[$currentSection] = [];
                }

                continue;
            }

            $item = $this->normalizeListItem($trimmed);

            if ($item !== null) {
                $sections[$currentSection][] = $item;

                continue;
            }

            if ($this->isLabeledValue($trimmed)) {
                [$label, $value] = $this->splitLabeledValue($trimmed);
                $sectionKey = $this->normalizeSectionKey($label);

                if ($value !== '') {
                    if (! isset($sections[$sectionKey])) {
                        $sections[$sectionKey] = [];
                    }

                    $sections[$sectionKey][] = $value;
                }

                continue;
            }

            $sections[$currentSection][] = $trimmed;
        }

        return $sections;
    }

    private function isSectionHeader(string $line): bool
    {
        if (preg_match('/^(#{1,3}\s+)?([A-Za-z][A-Za-z0-9\s\/\-&]+):\s*$/', $line) === 1) {
            return true;
        }

        if (preg_match('/^(#{1,3}\s+)?([A-Z][A-Za-z0-9\s\/\-&]{2,40})$/', $line) === 1) {
            return str_word_count($line) <= 5;
        }

        return false;
    }

    private function isLabeledValue(string $line): bool
    {
        return preg_match('/^([A-Za-z][A-Za-z0-9\s\/\-&]{1,30}):\s+(.+)$/', $line) === 1;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitLabeledValue(string $line): array
    {
        if (preg_match('/^([A-Za-z][A-Za-z0-9\s\/\-&]{1,30}):\s+(.+)$/', $line, $matches) !== 1) {
            return ['general', $line];
        }

        return [trim($matches[1]), trim($matches[2])];
    }

    private function normalizeSectionKey(string $header): string
    {
        $label = trim($header, "# \t");
        $label = rtrim($label, ':');

        return strtolower(preg_replace('/\s+/', '_', $label) ?? 'general');
    }

    private function normalizeListItem(string $line): ?string
    {
        if (preg_match('/^[-*•]\s+(.+)$/', $line, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/^\d+[.)]\s+(.+)$/', $line, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * @param  list<string>  $lines
     * @param  array<string, list<string>>  $sections
     */
    private function resolveProjectName(array $lines, array $sections, string $brief): string
    {
        foreach (['project', 'project_name', 'title', 'name'] as $key) {
            if (isset($sections[$key][0])) {
                $candidate = $this->condenseProjectName($sections[$key][0]);

                if ($candidate !== '' && ! $this->isGenericSectionLabel($candidate)) {
                    return $candidate;
                }
            }
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || $this->normalizeListItem($trimmed) !== null) {
                continue;
            }

            if ($this->isSectionHeader($trimmed) || $this->isLabeledValue($trimmed)) {
                continue;
            }

            if ($this->isGenericSectionLabel($trimmed)) {
                continue;
            }

            return $this->condenseProjectName($trimmed);
        }

        $fallbackLine = strtok($brief, "\n") ?: __('New strategic project');

        return $this->condenseProjectName($fallbackLine);
    }

    private function condenseProjectName(string $candidate): string
    {
        $clean = $this->cleanMarkdown(trim($candidate));

        if ($clean === '' || $this->isIncompletePhrase($clean)) {
            return '';
        }

        if (preg_match('/^(?:launch|build|create|implement|develop|deliver|roll out)\s+(.+)$/iu', $clean, $matches) === 1) {
            $clean = trim($matches[1]);
        }

        foreach ([' by ', ' for ', ' in order to ', ' to achieve ', ' — ', ' - '] as $delimiter) {
            $position = mb_stripos($clean, $delimiter);

            if ($position !== false && $position >= 8) {
                $clean = trim(mb_substr($clean, 0, $position));
                break;
            }
        }

        return $this->clipTitle($clean, self::MaxProjectNameLength);
    }

    /**
     * @param  array<string, list<string>>  $sections
     */
    private function resolveObjective(array $sections, string $brief): string
    {
        foreach (['objective', 'goal', 'goals', 'summary', 'overview', 'background'] as $key) {
            if (isset($sections[$key]) && $sections[$key] !== []) {
                return Utf8::truncate(implode(' ', $sections[$key]), 500);
            }
        }

        $scopeItems = $sections['scope'] ?? [];
        $successItems = $sections['success_criteria'] ?? $sections['success'] ?? [];

        if ($scopeItems !== [] || $successItems !== []) {
            $parts = [];

            if ($scopeItems !== []) {
                $parts[] = __('Scope: :items', ['items' => implode('; ', array_slice($scopeItems, 0, 3))]);
            }

            if ($successItems !== []) {
                $parts[] = __('Success: :items', ['items' => implode('; ', array_slice($successItems, 0, 3))]);
            }

            return Utf8::truncate(implode(' ', $parts), 500);
        }

        return Utf8::truncate($brief, 500);
    }

    private function isGenericSectionLabel(string $value): bool
    {
        return in_array(strtolower(trim($value)), [
            'title',
            'objective',
            'scope',
            'summary',
            'background',
            'project',
            'name',
            'timeline',
            'deliverables',
        ], true);
    }

    private function clipTitle(string $value, int $maxLength): string
    {
        return $this->clipAtWord($this->cleanMarkdown(trim($value)), $maxLength);
    }

    private function clipAtWord(string $value, int $maxLength): string
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

    private function cleanMarkdown(string $value): string
    {
        $clean = preg_replace('/\*\*(.+?)\*\*/u', '$1', $value) ?? $value;
        $clean = preg_replace('/`([^`]+)`/u', '$1', $clean) ?? $clean;

        return trim($clean);
    }

    private function isIncompletePhrase(string $value): bool
    {
        return preg_match('/\b(is a|is an|will be|should be|are a)\s*$/iu', trim($value)) === 1;
    }
}
