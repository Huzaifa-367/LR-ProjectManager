<?php

namespace App\Support;

final class OnboardingDynamicQuestionGenerator
{
    /**
     * @param  array<string, mixed>  $structure
     * @return list<array{key: string, label: string, prompt: string, hint: string, required: bool}>
     */
    public function generate(string $brief, array $structure, string $profileKey): array
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

    public function labelForKey(string $key): string
    {
        foreach ($this->allTemplates() as $question) {
            if ($question['key'] === $key) {
                return $question['label'];
            }
        }

        return ucfirst(str_replace('_', ' ', preg_replace('/^dynamic_/', '', $key) ?? $key));
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
                $this->maybeQuestion(
                    'dynamic_delivery',
                    ! preg_match('/\b(environment|staging|production|hosting|infra|stack|architecture)\b/i', $briefLower),
                    __('Delivery & environments'),
                    __('How should this be built, tested, and released?'),
                    __('Target environments, release approach, integrations, and acceptance or UAT expectations.'),
                ),
                $this->maybeQuestion(
                    'dynamic_scope_depth',
                    ($structure['work_item_count'] ?? 0) < 3,
                    __('Feature scope'),
                    __('What are the must-have capabilities for the first release?'),
                    __('List the minimum features or integrations required before go-live.'),
                ),
            ])),
            'training' => array_values(array_filter([
                $this->maybeQuestion(
                    'dynamic_delivery',
                    ! preg_match('/\b(online|virtual|in[\s-]?person|hybrid|self[\s-]?paced|live)\b/i', $briefLower),
                    __('Delivery format'),
                    __('How will the training be delivered?'),
                    __('Live vs self-paced, online vs in-person, duration, and session cadence.'),
                ),
                $this->maybeQuestion(
                    'dynamic_materials',
                    ! preg_match('/\b(material|workbook|slide|video|assessment|quiz|handout)\b/i', $briefLower),
                    __('Materials & assessment'),
                    __('What materials or assessments are required?'),
                    __('Slides, exercises, job aids, exams, and completion criteria.'),
                ),
            ])),
            'workshop' => array_values(array_filter([
                $this->maybeQuestion(
                    'dynamic_outcomes',
                    ! ($structure['has_success_criteria'] ?? false),
                    __('Session outcomes'),
                    __('What should participants leave the workshop with?'),
                    __('Decisions made, artifacts produced, next steps owned, or problems solved.'),
                ),
                $this->maybeQuestion(
                    'dynamic_logistics',
                    ! preg_match('/\b(venue|room|remote|zoom|teams|duration|half[\s-]?day|full[\s-]?day)\b/i', $briefLower),
                    __('Format & logistics'),
                    __('What is the workshop format and logistics?'),
                    __('Duration, location or remote setup, agenda blocks, and pre-work required.'),
                ),
            ])),
            'event' => array_values(array_filter([
                $this->maybeQuestion(
                    'dynamic_logistics',
                    ! preg_match('/\b(venue|catering|av|registration|run[\s-]?of[\s-]?show)\b/i', $briefLower),
                    __('Event logistics'),
                    __('What logistics must be planned for the event?'),
                    __('Venue, vendors, registration, AV, catering, and run-of-show milestones.'),
                ),
            ])),
            'operations' => array_values(array_filter([
                $this->maybeQuestion(
                    'dynamic_impact',
                    ! preg_match('/\b(impact|stakeholder|department|team|process\s+owner)\b/i', $briefLower),
                    __('Impact & owners'),
                    __('Which teams or processes are in scope and who owns the change?'),
                    __('Affected groups, process owners, and handoff points.'),
                ),
            ])),
            default => array_values(array_filter([
                $this->maybeQuestion(
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
    private function maybeQuestion(
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

    /**
     * @return list<array{key: string, label: string, prompt: string, hint: string, required: bool}>
     */
    private function allTemplates(): array
    {
        return [
            ['key' => 'dynamic_audience', 'label' => __('Audience & stakeholders'), 'prompt' => '', 'hint' => '', 'required' => false],
            ['key' => 'dynamic_delivery', 'label' => __('Delivery'), 'prompt' => '', 'hint' => '', 'required' => false],
            ['key' => 'dynamic_materials', 'label' => __('Materials'), 'prompt' => '', 'hint' => '', 'required' => false],
            ['key' => 'dynamic_outcomes', 'label' => __('Outcomes'), 'prompt' => '', 'hint' => '', 'required' => false],
            ['key' => 'dynamic_logistics', 'label' => __('Logistics'), 'prompt' => '', 'hint' => '', 'required' => false],
            ['key' => 'dynamic_impact', 'label' => __('Impact'), 'prompt' => '', 'hint' => '', 'required' => false],
            ['key' => 'dynamic_scope_depth', 'label' => __('Scope'), 'prompt' => '', 'hint' => '', 'required' => false],
            ['key' => 'dynamic_constraints', 'label' => __('Constraints & dependencies'), 'prompt' => '', 'hint' => '', 'required' => false],
        ];
    }
}
