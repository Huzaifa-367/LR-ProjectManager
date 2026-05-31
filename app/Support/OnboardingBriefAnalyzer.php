<?php

namespace App\Support;

final class OnboardingBriefAnalyzer
{
    public function __construct(
        private readonly OnboardingBriefParser $briefParser,
        private readonly OnboardingProjectProfileDetector $profileDetector,
        private readonly OnboardingDynamicQuestionGenerator $dynamicQuestionGenerator,
    ) {}

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
        $profile = $this->profileDetector->detect($brief, $structure);

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
            ? $this->dynamicQuestionGenerator->generate($brief, $structure, $profile['key'])
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
            $question['input_mode'] = OnboardingQuestionSuggestions::inputModeFor($question['key']);
            $question['suggestions'] = OnboardingQuestionSuggestions::for($question['key'], $profileKey);

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
}
