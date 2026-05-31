<?php

namespace App\Support;

final class OnboardingBriefParser
{
    private const int MaxTasks = 15;

    private const int MaxTaskTitleLength = 58;

    private const int MaxDecisionTitleLength = 58;

    private const int MaxReminderTitleLength = 50;

    private const int MaxTaskDescriptionLength = 480;

    private const int MaxProjectNameLength = 55;

    private string $projectNameContext = '';

    /**
     * @return array{
     *     project_name: string,
     *     objective: string,
     *     next_action: string,
     *     tasks: list<array<string, mixed>>,
     *     decisions: list<array<string, mixed>>,
     *     reminders: list<array<string, mixed>>
     * }
     */
    public function parse(string $brief, int $leadMemberId): array
    {
        $normalizedBrief = trim(Utf8::sanitize($brief));

        if ($normalizedBrief === '') {
            return $this->fallbackPlan(__('New strategic project'), '', $leadMemberId);
        }

        $lines = preg_split('/\r\n|\r|\n/', $normalizedBrief) ?: [];
        $sections = $this->parseSections($lines);
        $projectName = $this->resolveProjectName($lines, $sections, $normalizedBrief);
        $this->projectNameContext = $projectName;
        $objective = $this->resolveObjective($sections, $normalizedBrief);
        $deadlineType = $this->inferDeadlineType($normalizedBrief);
        $priorityBias = $this->inferPriorityBias($normalizedBrief);

        $tasks = $this->buildTasksFromSections($sections, $leadMemberId, $deadlineType, $priorityBias);

        if ($tasks === []) {
            $tasks = $this->buildTasksFromUnstructuredBrief($lines, $leadMemberId, $deadlineType);
        }

        $tasks = $this->ensureMinimumTasks($tasks, $projectName, $objective, $leadMemberId, $deadlineType);
        $tasks = array_slice($tasks, 0, self::MaxTasks);

        $decisions = $this->buildDecisions($sections, $normalizedBrief, $leadMemberId);
        $reminders = $this->buildReminders($sections, $normalizedBrief, $projectName, $leadMemberId);

        $nextAction = $tasks[0]['title'] ?? __('Review generated task plan');

        return [
            'project_name' => $projectName,
            'objective' => $objective,
            'next_action' => $nextAction,
            'tasks' => $tasks,
            'decisions' => $decisions,
            'reminders' => $reminders,
        ];
    }

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
        $clean = OnboardingText::cleanMarkdown(trim($candidate));

        if ($clean === '' || OnboardingText::isIncompletePhrase($clean)) {
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

        return OnboardingText::clipTitle($clean, self::MaxProjectNameLength);
    }

    /**
     * @param  array<string, list<string>>  $sections
     */
    private function resolveObjective(array $sections, string $brief): string
    {
        foreach (['objective', 'goal', 'goals', 'summary', 'overview', 'background'] as $key) {
            if (isset($sections[$key]) && $sections[$key] !== []) {
                return $this->truncate(implode(' ', $sections[$key]), 500);
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

            return $this->truncate(implode(' ', $parts), 500);
        }

        return $this->truncate($brief, 500);
    }

    /**
     * @param  array<string, list<string>>  $sections
     * @return list<array<string, mixed>>
     */
    private function buildTasksFromSections(
        array $sections,
        int $leadMemberId,
        string $deadlineType,
        string $defaultPriority,
    ): array {
        $tasks = [];
        $taskSections = [
            'scope' => ['prefix' => '', 'priority' => 'high'],
            'deliverables' => ['prefix' => '', 'priority' => 'high'],
            'requirements' => ['prefix' => '', 'priority' => 'high'],
            'success_criteria' => ['prefix' => '', 'priority' => 'high'],
            'success' => ['prefix' => '', 'priority' => 'high'],
            'workstreams' => ['prefix' => '', 'priority' => 'medium'],
            'curriculum' => ['prefix' => '', 'priority' => 'high'],
            'modules' => ['prefix' => __('Module'), 'priority' => 'medium'],
            'agenda' => ['prefix' => __('Agenda'), 'priority' => 'medium'],
            'program' => ['prefix' => '', 'priority' => 'medium'],
            'activities' => ['prefix' => '', 'priority' => 'medium'],
            'phases' => ['prefix' => __('Phase'), 'priority' => 'medium'],
            'timeline' => ['prefix' => __('Timeline'), 'priority' => 'medium'],
            'milestones' => ['prefix' => __('Milestone'), 'priority' => 'high'],
            'actions' => ['prefix' => '', 'priority' => 'medium'],
            'next_steps' => ['prefix' => '', 'priority' => 'high'],
            'tasks' => ['prefix' => '', 'priority' => 'medium'],
            'implementation' => ['prefix' => '', 'priority' => 'medium'],
            'rollout' => ['prefix' => __('Rollout'), 'priority' => 'high'],
            'constraints' => ['prefix' => __('Constraint'), 'priority' => 'high'],
            'dependencies' => ['prefix' => __('Dependency'), 'priority' => 'medium'],
            'risks' => ['prefix' => __('Risk mitigation'), 'priority' => 'medium'],
        ];

        foreach ($taskSections as $sectionKey => $config) {
            foreach ($sections[$sectionKey] ?? [] as $item) {
                if ($this->looksLikeDecision($item)) {
                    continue;
                }

                $tasks[] = $this->makeTask(
                    $this->formatTaskTitle($item, $config['prefix']),
                    $this->formatTaskDescription($item, $sectionKey),
                    $this->resolveItemPriority($item, $config['priority'], $defaultPriority),
                    $deadlineType,
                    $leadMemberId,
                );
            }
        }

        foreach ($sections['general'] ?? [] as $item) {
            if ($this->looksLikeDecision($item) || $this->isMetaLine($item)) {
                continue;
            }

            if (strlen($item) < 12) {
                continue;
            }

            $tasks[] = $this->makeTask(
                $this->formatTaskTitle($item),
                $this->formatTaskDescription($item, 'general'),
                $this->resolveItemPriority($item, 'medium', $defaultPriority),
                $deadlineType,
                $leadMemberId,
            );
        }

        return $this->dedupeTasks($tasks);
    }

    /**
     * @param  list<string>  $lines
     * @return list<array<string, mixed>>
     */
    private function buildTasksFromUnstructuredBrief(array $lines, int $leadMemberId, string $deadlineType): array
    {
        $tasks = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            $item = $this->normalizeListItem($trimmed) ?? ($this->isLabeledValue($trimmed) ? $this->splitLabeledValue($trimmed)[1] : null);

            if ($item === null || $this->looksLikeDecision($item) || $this->isMetaLine($item)) {
                continue;
            }

            $tasks[] = $this->makeTask(
                $this->formatTaskTitle($item),
                $this->formatTaskDescription($item, 'brief'),
                $this->resolveItemPriority($item, 'medium', 'medium'),
                $deadlineType,
                $leadMemberId,
            );
        }

        return $this->dedupeTasks($tasks);
    }

    /**
     * @param  list<array<string, mixed>>  $tasks
     * @return list<array<string, mixed>>
     */
    private function ensureMinimumTasks(
        array $tasks,
        string $projectName,
        string $objective,
        int $leadMemberId,
        string $deadlineType,
    ): array {
        if (count($tasks) >= 2) {
            return $tasks;
        }

        $staples = [
            [
                'title' => __('Stakeholder kickoff'),
                'description' => __('Align sponsors on :project cadence, owners, and escalation paths.', ['project' => $projectName]),
                'priority' => 'high',
            ],
            [
                'title' => __('Scope & success metrics'),
                'description' => $objective !== ''
                    ? $objective
                    : __('Document deliverables, success criteria, and how progress will be tracked.'),
                'priority' => 'high',
            ],
            [
                'title' => __('Timeline & ownership map'),
                'description' => __('Sequence milestones with named owners, deadlines, and dependency notes.'),
                'priority' => 'medium',
            ],
            [
                'title' => __('Risk & dependency log'),
                'description' => __('Capture blockers, constraints, and mitigations with review dates.'),
                'priority' => 'medium',
            ],
        ];

        foreach ($staples as $staple) {
            if (count($tasks) >= 5) {
                break;
            }

            $tasks[] = $this->makeTask(
                $staple['title'],
                $staple['description'],
                $staple['priority'],
                $deadlineType,
                $leadMemberId,
            );
        }

        return $this->dedupeTasks($tasks);
    }

    /**
     * @param  array<string, list<string>>  $sections
     * @return list<array<string, mixed>>
     */
    private function buildDecisions(array $sections, string $brief, int $leadMemberId): array
    {
        $decisions = [];
        $sortOrder = 1;

        foreach (['decisions', 'decisions_needed', 'approvals', 'open_questions'] as $sectionKey) {
            foreach ($sections[$sectionKey] ?? [] as $item) {
                $decisions[] = $this->makeDecision($this->formatDecisionTitle($item), $sortOrder++, $leadMemberId);
            }
        }

        foreach ($sections as $items) {
            foreach ($items as $item) {
                if (! $this->looksLikeDecision($item)) {
                    continue;
                }

                $decisions[] = $this->makeDecision($this->formatDecisionTitle($item), $sortOrder++, $leadMemberId);
            }
        }

        $decisions = $this->dedupeByTitle($decisions);

        if ($decisions === []) {
            $decisions[] = $this->makeDecision(__('Approve project charter and initial plan'), 1, $leadMemberId);
        }

        if (preg_match('/budget|funding|investment/i', $brief) === 1) {
            $decisions[] = $this->makeDecision(__('Approve initial budget envelope'), $sortOrder, $leadMemberId);
        }

        return array_slice($this->dedupeByTitle($decisions), 0, 8);
    }

    /**
     * @param  array<string, list<string>>  $sections
     * @return list<array<string, mixed>>
     */
    private function buildReminders(
        array $sections,
        string $brief,
        string $projectName,
        int $leadMemberId,
    ): array {
        $reminders = [];

        foreach (['reminders', 'check_ins', 'cadence', 'steerco'] as $sectionKey) {
            foreach ($sections[$sectionKey] ?? [] as $item) {
                $reminders[] = [
                    'title' => $this->truncate($this->shortenTaskTitle($this->cleanItem($item)), self::MaxReminderTitleLength),
                    'description' => $this->formatReminderDescription($item, $projectName),
                    'meta' => ['icon' => '📅', 'is_urgent' => false],
                    'assignee_member_ids' => [$leadMemberId],
                ];
            }
        }

        if ($reminders === []) {
            $reminders[] = [
                'title' => __('Weekly exec sync'),
                'description' => __('Recurring check-in on status, blockers, and next actions for :project.', ['project' => $projectName]),
                'meta' => ['icon' => '📅', 'is_urgent' => false],
                'assignee_member_ids' => [$leadMemberId],
            ];
        }

        if (preg_match('/steerco|steering committee|exec(?:utive)?\s+sync/i', $brief) === 1) {
            $reminders[] = [
                'title' => __('Steerco / executive sync'),
                'description' => __('Recurring leadership review for :project.', ['project' => $projectName]),
                'meta' => ['icon' => '👔', 'is_urgent' => false],
                'assignee_member_ids' => [$leadMemberId],
            ];
        }

        if (preg_match('/daily standup|daily sync/i', $brief) === 1) {
            $reminders[] = [
                'title' => __('Daily team standup'),
                'description' => __('Short sync on in-flight work and blockers.'),
                'meta' => ['icon' => '⏱️', 'is_urgent' => false],
                'assignee_member_ids' => [$leadMemberId],
            ];
        }

        return $reminders;
    }

    /**
     * @return array{
     *     project_name: string,
     *     objective: string,
     *     next_action: string,
     *     tasks: list<array<string, mixed>>,
     *     decisions: list<array<string, mixed>>,
     *     reminders: list<array<string, mixed>>
     * }
     */
    private function fallbackPlan(string $projectName, string $objective, int $leadMemberId): array
    {
        $deadlineType = 'this_week';
        $tasks = $this->ensureMinimumTasks([], $projectName, $objective, $leadMemberId, $deadlineType);

        return [
            'project_name' => $projectName,
            'objective' => $objective,
            'next_action' => $tasks[0]['title'],
            'tasks' => $tasks,
            'decisions' => [$this->makeDecision(__('Approve project charter and initial plan'), 1, $leadMemberId)],
            'reminders' => $this->buildReminders([], '', $projectName, $leadMemberId),
        ];
    }

    private function makeTask(
        string $title,
        string $description,
        string $priority,
        string $deadlineType,
        int $leadMemberId,
    ): array {
        return [
            'title' => $this->truncate($title, self::MaxTaskTitleLength),
            'description' => $this->truncate($description, self::MaxTaskDescriptionLength),
            'priority' => $priority,
            'status' => 'pending',
            'deadline_type' => $deadlineType,
            'deadline_date' => null,
            'assignee_member_ids' => [$leadMemberId],
            'kind' => 'task',
        ];
    }

    private function makeDecision(string $title, int $sortOrder, int $leadMemberId): array
    {
        return [
            'title' => $this->truncate($title, self::MaxDecisionTitleLength),
            'sort_order' => $sortOrder,
            'assignee_member_ids' => [$leadMemberId],
        ];
    }

    private function formatTaskTitle(string $item, string $prefix = ''): string
    {
        $short = $this->shortenTaskTitle($this->cleanItem($item));

        if ($prefix !== '' && ! str_contains(strtolower($short), strtolower($prefix))) {
            $short = $prefix.': '.$short;
        }

        return $this->truncate($short, self::MaxTaskTitleLength);
    }

    private function shortenTaskTitle(string $detail): string
    {
        $work = preg_replace(
            '/^(complete|conduct|finalize|prepare|develop|implement|establish|create|build|deliver|perform|coordinate|organize|review and|set up)\s+/iu',
            '',
            $detail,
        ) ?? $detail;

        $work = trim($work);

        if (mb_strlen($work) > self::MaxTaskTitleLength) {
            foreach ([' — ', ' - ', ', ', '; '] as $delimiter) {
                $position = mb_stripos($work, $delimiter);

                if ($position !== false && $position >= 10) {
                    $work = trim(mb_substr($work, 0, $position));
                    break;
                }
            }
        }

        $words = preg_split('/\s+/u', $work) ?: [];

        if (count($words) > 7) {
            $work = implode(' ', array_slice($words, 0, 7));
        }

        $normalized = trim($work);

        if ($normalized === '') {
            return $this->truncate($detail, self::MaxTaskTitleLength);
        }

        return $this->truncate(ucfirst($normalized), self::MaxTaskTitleLength);
    }

    private function formatTaskDescription(string $item, string $sectionKey): string
    {
        $detail = $this->cleanItem($item);
        $sectionLabel = ucfirst(str_replace('_', ' ', $sectionKey));
        $parts = [];

        if ($sectionKey !== 'general' && $sectionKey !== 'brief') {
            $parts[] = __('Focus area: :section', ['section' => $sectionLabel]);
        }

        $parts[] = __('Work: :detail', ['detail' => $detail]);

        if ($this->projectNameContext !== '') {
            $parts[] = __('Project: :project', ['project' => $this->projectNameContext]);
        }

        $parts[] = __('Done when: output is accepted, owners are clear, and dependencies are noted.');

        return implode("\n", $parts);
    }

    private function formatReminderDescription(string $item, string $projectName): string
    {
        $detail = $this->cleanItem($item);

        return implode("\n", [
            __('Cadence: :detail', ['detail' => $detail]),
            __('Project: :project', ['project' => $projectName]),
            __('Use for recurring visibility on progress and blockers.'),
        ]);
    }

    private function formatDecisionTitle(string $item): string
    {
        $short = $this->shortenTaskTitle($this->cleanItem($item));

        if (preg_match('/^(approve|decide|select|choose|confirm)\b/i', $short) === 1) {
            return $this->truncate(ucfirst($short), self::MaxDecisionTitleLength);
        }

        return $this->truncate(__('Decide: :item', ['item' => $short]), self::MaxDecisionTitleLength);
    }

    private function cleanItem(string $item): string
    {
        $clean = trim($item);
        $clean = preg_replace('/^(task|action|todo):\s*/i', '', $clean) ?? $clean;

        return trim($clean);
    }

    private function looksLikeDecision(string $item): bool
    {
        return preg_match('/\b(approve|decision|decide|select|choose|sign[\s-]?off|go\/no[\s-]?go|pick between)\b/i', $item) === 1;
    }

    private function isMetaLine(string $item): bool
    {
        return preg_match('/^(objective|goal|project|timeline|success|scope):/i', $item) === 1;
    }

    private function inferDeadlineType(string $brief): string
    {
        if (preg_match('/\b(today|asap|urgent|immediately)\b/i', $brief) === 1) {
            return 'today';
        }

        if (preg_match('/\b(this week|by friday|end of week)\b/i', $brief) === 1) {
            return 'this_week';
        }

        if (preg_match('/\b(deadline|due|by \d|before \d|\d{4}-\d{2}-\d{2})\b/i', $brief) === 1) {
            return 'date';
        }

        if (preg_match('/\b(this month|end of month|eom|within 30 days|q[1-4]|quarter|h[12]|6 months)\b/i', $brief) === 1) {
            return 'this_week';
        }

        return 'none';
    }

    private function inferPriorityBias(string $brief): string
    {
        if (preg_match('/\b(critical|urgent|blocker|must[\s-]?have|high priority)\b/i', $brief) === 1) {
            return 'high';
        }

        return 'medium';
    }

    private function resolveItemPriority(string $item, string $sectionPriority, string $defaultPriority): string
    {
        if (preg_match('/\b(critical|urgent|blocker|must|asap|high priority)\b/i', $item) === 1) {
            return 'high';
        }

        if (preg_match('/\b(nice to have|optional|low priority|when possible)\b/i', $item) === 1) {
            return 'low';
        }

        if ($sectionPriority === 'high' || $defaultPriority === 'high') {
            return 'high';
        }

        return $sectionPriority;
    }

    /**
     * @param  list<array<string, mixed>>  $tasks
     * @return list<array<string, mixed>>
     */
    private function dedupeTasks(array $tasks): array
    {
        return $this->dedupeByTitle($tasks);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function dedupeByTitle(array $items): array
    {
        $seen = [];
        $unique = [];

        foreach ($items as $item) {
            $key = strtolower(trim((string) ($item['title'] ?? '')));

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    private function truncate(string $value, int $maxLength): string
    {
        return Utf8::truncate($value, $maxLength);
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
}
