<?php

namespace App\Support;

final class OnboardingBriefAnalyzer
{
    public function __construct(
        private readonly OnboardingBriefParser $briefParser,
    ) {}

    /**
     * @return array{key: string, label: string, summary: string}
     */
    public function detectProfile(string $brief): array
    {
        return $this->detectProfileFromStructure(
            $brief,
            $this->briefParser->extractStructure($brief),
        );
    }

    /**
     * @return array{
     *     is_complete: bool,
     *     phase: string,
     *     project_profile: array{key: string, label: string, summary: string},
     *     detected: array<string, string|null>,
     *     missing_required: list<string>,
     *     missing_optional: list<string>,
     *     questions: list<array{key: string, label: string, prompt: string, hint: string, required: bool, input_mode: string, suggestions: list<array{label: string, value: string, mode: string}>}>,
     *     readiness_percent: int
     * }
     */
    public function assess(string $brief, int $teamMemberCount, bool $hasPriorSubmission = false): array
    {
        $structure = $this->briefParser->extractStructure($brief);
        $profile = $this->detectProfileFromStructure($brief, $structure);

        $detected = [
            'project_name' => $structure['project_name'],
            'objective' => $structure['objective'],
            'work_items' => $structure['work_item_count'] > 0
                ? (string) $structure['work_item_count'].' '.__('items detected')
                : null,
            'timeline' => $structure['has_timeline'] ? __('Timeline provided') : null,
            'success_criteria' => $structure['has_success_criteria'] ? __('Success criteria provided') : null,
            'decisions' => $structure['has_decisions'] ? __('Decisions provided') : null,
            'reminders' => $structure['has_reminders'] ? __('Check-ins provided') : null,
            'team' => $teamMemberCount > 0
                ? (string) $teamMemberCount.' '.__('members selected')
                : null,
        ];

        $missingRequired = [];
        $missingOptional = [];

        if (! $this->hasProjectName($structure)) {
            $missingRequired[] = 'project_name';
        }

        if (! $this->hasObjective($structure)) {
            $missingRequired[] = 'objective';
        }

        if ($structure['work_item_count'] < 2) {
            $missingRequired[] = 'work_items';
        }

        if (! $structure['has_timeline']) {
            $missingRequired[] = 'timeline';
        }

        if ($teamMemberCount < 1) {
            $missingRequired[] = 'team';
        }

        if (! $structure['has_success_criteria']) {
            $missingOptional[] = 'success_criteria';
        }

        if (! $structure['has_decisions']) {
            $missingOptional[] = 'decisions';
        }

        if (! $structure['has_reminders']) {
            $missingOptional[] = 'reminders';
        }

        $definedQuestions = $this->buildDefinedQuestions($missingRequired, $missingOptional, $profile['key']);
        $dynamicQuestions = $hasPriorSubmission || $brief !== ''
            ? $this->generateDynamicQuestions($brief, $structure, $profile['key'])
            : [];

        $questions = $this->enrichQuestions(
            $this->mergeQuestions($definedQuestions, $dynamicQuestions),
            $profile['key'],
        );

        $requiredTotal = 5;
        $requiredMet = $requiredTotal - count($missingRequired);
        $readinessPercent = (int) round(max(0, $requiredMet) / $requiredTotal * 100);
        $phase = $this->resolvePhase($hasPriorSubmission, $missingRequired, $brief);

        return [
            'is_complete' => $missingRequired === [],
            'phase' => $phase,
            'project_profile' => $profile,
            'detected' => $detected,
            'missing_required' => $missingRequired,
            'missing_optional' => $missingOptional,
            'questions' => $questions,
            'readiness_percent' => $readinessPercent,
        ];
    }

    /**
     * @param  array<string, mixed>  $structure
     */
    private function hasProjectName(array $structure): bool
    {
        $name = trim((string) ($structure['project_name'] ?? ''));

        if ($name === '' || $name === __('New strategic project')) {
            return false;
        }

        return mb_strlen($name) >= 3;
    }

    /**
     * @param  array<string, mixed>  $structure
     */
    private function hasObjective(array $structure): bool
    {
        $objective = trim((string) ($structure['objective'] ?? ''));

        return mb_strlen($objective) >= 20;
    }

    /**
     * @param  list<string>  $missingRequired
     * @param  list<string>  $missingOptional
     * @return list<array{key: string, label: string, prompt: string, hint: string, required: bool}>
     */
    private function buildDefinedQuestions(array $missingRequired, array $missingOptional, string $profileKey): array
    {
        $questions = [];

        foreach ($missingRequired as $key) {
            if ($key === 'team') {
                $questions[] = [
                    'key' => 'team',
                    'label' => __('Project team'),
                    'prompt' => __('Select at least one project team member before generating the plan.'),
                    'hint' => __('The lead is used as the default assignee for generated work items.'),
                    'required' => true,
                ];

                continue;
            }

            $definition = OnboardingRequirementRegistry::definitionFor($key, $profileKey);

            if ($definition === null) {
                continue;
            }

            $questions[] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'prompt' => $definition['prompt'],
                'hint' => $definition['hint'],
                'required' => true,
            ];
        }

        foreach (array_slice($missingOptional, 0, 2) as $key) {
            $definition = OnboardingRequirementRegistry::definitionFor($key, $profileKey);

            if ($definition === null) {
                continue;
            }

            $questions[] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'prompt' => $definition['prompt'],
                'hint' => $definition['hint'],
                'required' => false,
            ];
        }

        return $questions;
    }

    /**
     * @param  list<array{key: string, label: string, prompt: string, hint: string, required: bool}>  $defined
     * @param  list<array{key: string, label: string, prompt: string, hint: string, required: bool}>  $dynamic
     * @return list<array{key: string, label: string, prompt: string, hint: string, required: bool}>
     */
    private function mergeQuestions(array $defined, array $dynamic): array
    {
        $merged = $defined;
        $keys = array_map(fn (array $question): string => $question['key'], $defined);

        foreach ($dynamic as $question) {
            if (in_array($question['key'], $keys, true)) {
                continue;
            }

            $merged[] = $question;
            $keys[] = $question['key'];
        }

        return $merged;
    }

    /**
     * @param  list<array{key: string, label: string, prompt: string, hint: string, required: bool}>  $questions
     * @return list<array{key: string, label: string, prompt: string, hint: string, required: bool, input_mode: string, suggestions: list<array{label: string, value: string, mode: string}>}>
     */
    private function enrichQuestions(array $questions, string $profileKey): array
    {
        return array_map(function (array $question) use ($profileKey): array {
            $question['input_mode'] = OnboardingRequirementRegistry::inputModeFor($question['key']);
            $question['suggestions'] = OnboardingRequirementRegistry::suggestionsFor($question['key'], $profileKey);

            return $question;
        }, $questions);
    }

    /**
     * @param  list<string>  $missingRequired
     */
    private function resolvePhase(bool $hasPriorSubmission, array $missingRequired, string $brief): string
    {
        if (trim($brief) === '') {
            return $hasPriorSubmission ? 'follow_up' : 'initial';
        }

        if ($missingRequired !== []) {
            return 'follow_up';
        }

        return 'ready';
    }

    /**
     * @param  array<string, mixed>  $structure
     * @return array{key: string, label: string, summary: string}
     */
    private function detectProfileFromStructure(string $brief, array $structure): array
    {
        $haystack = strtolower($brief.' '.implode(' ', $structure['sections']['general'] ?? []));

        $profiles = [
            'software' => [
                'label' => __('Software / product delivery'),
                'summary' => __('Build, release, or integrate a technical solution.'),
                'patterns' => '/\b(software|application|app|platform|api|integration|deploy|release|sprint|uat|qa|backend|frontend|database|feature|bug|code|devops|migration)\b/i',
            ],
            'training' => [
                'label' => __('Training / learning program'),
                'summary' => __('Design and deliver structured learning outcomes.'),
                'patterns' => '/\b(training|course|curriculum|learners?|participants?|certification|module|lesson|cohort|lms|workbook|instructor)\b/i',
            ],
            'workshop' => [
                'label' => __('Workshop / facilitated session'),
                'summary' => __('Plan a focused working session with clear outputs.'),
                'patterns' => '/\b(workshop|facilitat|brainstorm|design\s+thinking|breakout|agenda|session\s+plan|offsite)\b/i',
            ],
            'event' => [
                'label' => __('Event / program launch'),
                'summary' => __('Coordinate people, logistics, and run-of-show.'),
                'patterns' => '/\b(event|conference|summit|launch\s+event|venue|speaker|registration|expo|gala)\b/i',
            ],
            'operations' => [
                'label' => __('Operations / business initiative'),
                'summary' => __('Improve processes, policies, or organizational outcomes.'),
                'patterns' => '/\b(process|policy|operating\s+model|rollout|change\s+management|vendor|procurement|compliance|audit)\b/i',
            ],
        ];

        foreach ($profiles as $key => $profile) {
            if (preg_match($profile['patterns'], $haystack) === 1) {
                return [
                    'key' => $key,
                    'label' => $profile['label'],
                    'summary' => $profile['summary'],
                ];
            }
        }

        return [
            'key' => 'general',
            'label' => __('General project'),
            'summary' => __('Plan work from the details you provide — any domain or format.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $structure
     * @return list<array{key: string, label: string, prompt: string, hint: string, required: bool}>
     */
    private function generateDynamicQuestions(string $brief, array $structure, string $profileKey): array
    {
        $questions = [];
        $briefLower = strtolower($brief);

        if (! $this->mentionsAudience($briefLower, $structure)) {
            $questions[] = $this->audienceQuestion($profileKey);
        }

        foreach ($this->profileQuestions($profileKey, $briefLower, $structure) as $question) {
            $questions[] = $question;
        }

        if (! $this->mentionsConstraints($briefLower, $structure) && count($questions) < 4) {
            $questions[] = [
                'key' => 'dynamic_constraints',
                'label' => __('Constraints & dependencies'),
                'prompt' => __('What constraints, dependencies, or blockers should the plan account for?'),
                'hint' => __('Budget limits, approvals, tools, vendors, availability, compliance, or external deadlines.'),
                'required' => false,
            ];
        }

        return $this->dedupeQuestions(array_slice($questions, 0, 4));
    }

    /**
     * @return array{key: string, label: string, prompt: string, hint: string, required: bool}
     */
    private function audienceQuestion(string $profileKey): array
    {
        return match ($profileKey) {
            'software' => [
                'key' => 'dynamic_audience',
                'label' => __('Users & stakeholders'),
                'prompt' => __('Who will use or be affected by this solution, and who must sign off?'),
                'hint' => __('End users, admins, sponsors, and teams responsible for adoption or support.'),
                'required' => false,
            ],
            'training' => [
                'key' => 'dynamic_audience',
                'label' => __('Learners & stakeholders'),
                'prompt' => __('Who is the target audience and who owns training success?'),
                'hint' => __('Role, seniority, cohort size, prerequisites, and executive sponsor.'),
                'required' => false,
            ],
            'workshop' => [
                'key' => 'dynamic_audience',
                'label' => __('Participants'),
                'prompt' => __('Who should attend the workshop and what roles do they play?'),
                'hint' => __('Decision makers, contributors, observers, and expected headcount.'),
                'required' => false,
            ],
            'event' => [
                'key' => 'dynamic_audience',
                'label' => __('Attendees & stakeholders'),
                'prompt' => __('Who is the event for and who are the key organizers or sponsors?'),
                'hint' => __('Audience profile, invite list size, speakers, and production owners.'),
                'required' => false,
            ],
            default => [
                'key' => 'dynamic_audience',
                'label' => __('Audience & stakeholders'),
                'prompt' => __('Who is this project for and who needs to stay informed or approve work?'),
                'hint' => __('Primary beneficiaries, owners, and anyone impacted by delivery.'),
                'required' => false,
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $structure
     * @return list<array{key: string, label: string, prompt: string, hint: string, required: bool}>
     */
    private function profileQuestions(string $profileKey, string $briefLower, array $structure): array
    {
        return match ($profileKey) {
            'software' => array_values(array_filter([
                $this->maybeDynamicQuestion(
                    'dynamic_delivery',
                    ! preg_match('/\b(environment|staging|production|hosting|infra|stack|architecture)\b/i', $briefLower),
                    __('Delivery & environments'),
                    __('How should this be built, tested, and released?'),
                    __('Target environments, release approach, integrations, and acceptance or UAT expectations.'),
                ),
                $this->maybeDynamicQuestion(
                    'dynamic_scope_depth',
                    ($structure['work_item_count'] ?? 0) < 3,
                    __('Feature scope'),
                    __('What are the must-have capabilities for the first release?'),
                    __('List the minimum features or integrations required before go-live.'),
                ),
            ])),
            'training' => array_values(array_filter([
                $this->maybeDynamicQuestion(
                    'dynamic_delivery',
                    ! preg_match('/\b(online|virtual|in[\s-]?person|hybrid|self[\s-]?paced|live)\b/i', $briefLower),
                    __('Delivery format'),
                    __('How will the training be delivered?'),
                    __('Live vs self-paced, online vs in-person, duration, and session cadence.'),
                ),
                $this->maybeDynamicQuestion(
                    'dynamic_materials',
                    ! preg_match('/\b(material|workbook|slide|video|assessment|quiz|handout)\b/i', $briefLower),
                    __('Materials & assessment'),
                    __('What materials or assessments are required?'),
                    __('Slides, exercises, job aids, exams, and completion criteria.'),
                ),
            ])),
            'workshop' => array_values(array_filter([
                $this->maybeDynamicQuestion(
                    'dynamic_outcomes',
                    ! ($structure['has_success_criteria'] ?? false),
                    __('Session outcomes'),
                    __('What should participants leave the workshop with?'),
                    __('Decisions made, artifacts produced, next steps owned, or problems solved.'),
                ),
                $this->maybeDynamicQuestion(
                    'dynamic_logistics',
                    ! preg_match('/\b(venue|room|remote|zoom|teams|duration|half[\s-]?day|full[\s-]?day)\b/i', $briefLower),
                    __('Format & logistics'),
                    __('What is the workshop format and logistics?'),
                    __('Duration, location or remote setup, agenda blocks, and pre-work required.'),
                ),
            ])),
            'event' => array_values(array_filter([
                $this->maybeDynamicQuestion(
                    'dynamic_logistics',
                    ! preg_match('/\b(venue|catering|av|registration|run[\s-]?of[\s-]?show)\b/i', $briefLower),
                    __('Event logistics'),
                    __('What logistics must be planned for the event?'),
                    __('Venue, vendors, registration, AV, catering, and run-of-show milestones.'),
                ),
            ])),
            'operations' => array_values(array_filter([
                $this->maybeDynamicQuestion(
                    'dynamic_impact',
                    ! preg_match('/\b(impact|stakeholder|department|team|process\s+owner)\b/i', $briefLower),
                    __('Impact & owners'),
                    __('Which teams or processes are in scope and who owns the change?'),
                    __('Affected groups, process owners, and handoff points.'),
                ),
            ])),
            default => array_values(array_filter([
                $this->maybeDynamicQuestion(
                    'dynamic_outcomes',
                    ! ($structure['has_success_criteria'] ?? false),
                    __('Expected outcomes'),
                    __('What does success look like when this project is done?'),
                    __('Measurable results, deliverables accepted, or behavior that should change.'),
                ),
            ])),
        };
    }

    /**
     * @return array{key: string, label: string, prompt: string, hint: string, required: bool}|null
     */
    private function maybeDynamicQuestion(
        string $key,
        bool $needed,
        string $label,
        string $prompt,
        string $hint,
    ): ?array {
        if (! $needed) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'prompt' => $prompt,
            'hint' => $hint,
            'required' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $structure
     */
    private function mentionsAudience(string $briefLower, array $structure): bool
    {
        if (preg_match('/\b(audience|stakeholder|participant|attendee|learner|user|sponsor|beneficiar)\b/i', $briefLower) === 1) {
            return true;
        }

        foreach (['audience', 'stakeholders', 'participants', 'learners'] as $sectionKey) {
            if (($structure['sections'][$sectionKey] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $structure
     */
    private function mentionsConstraints(string $briefLower, array $structure): bool
    {
        if (preg_match('/\b(constraint|dependency|risk|blocker|budget|compliance|limitation)\b/i', $briefLower) === 1) {
            return true;
        }

        foreach (['constraints', 'dependencies', 'risks'] as $sectionKey) {
            if (($structure['sections'][$sectionKey] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{key: string, label: string, prompt: string, hint: string, required: bool}>  $questions
     * @return list<array{key: string, label: string, prompt: string, hint: string, required: bool}>
     */
    private function dedupeQuestions(array $questions): array
    {
        $seen = [];
        $unique = [];

        foreach ($questions as $question) {
            if (isset($seen[$question['key']])) {
                continue;
            }

            $seen[$question['key']] = true;
            $unique[] = $question;
        }

        return $unique;
    }
}
