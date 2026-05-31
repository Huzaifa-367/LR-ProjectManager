<?php

namespace App\Support;

final class OnboardingRequirementRegistry
{
    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     required: bool,
     *     prompt: string,
     *     hint: string
     * }>
     */
    public static function definitions(): array
    {
        return [
            self::definition('project_name', true),
            self::definition('objective', true),
            self::definition('work_items', true),
            self::definition('timeline', true),
            self::definition('success_criteria', false),
            self::definition('decisions', false),
            self::definition('reminders', false),
        ];
    }

    public static function initialPastePlaceholder(): string
    {
        return <<<'TXT'
Paste everything you have — any project type is supported (software, training, workshop, event, operations, etc.).

Project: …
Objective: …
Scope / deliverables:
- …
- …
Timeline: …
Success criteria: …
Decisions needed: …
TXT;
    }

    public static function initialPasteGuide(): string
    {
        return __('Paste notes, emails, proposals, or bullet lists. The assistant detects the project type and only asks for gaps — it does not invent details.');
    }

    /**
     * @return list<array{label: string, detail: string}>
     */
    public static function wizardSteps(): array
    {
        return [
            ['label' => 'Details', 'detail' => 'Paste what you know about the project'],
            ['label' => 'Questions', 'detail' => 'Answer tailored follow-ups for missing context'],
            ['label' => 'Review', 'detail' => 'Edit the generated plan'],
            ['label' => 'Apply', 'detail' => 'Create the project and work items'],
        ];
    }

    public static function labelFor(string $key): string
    {
        if (str_starts_with($key, 'dynamic_')) {
            return app(OnboardingDynamicQuestionGenerator::class)->labelForKey($key);
        }

        foreach (self::definitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition['label'];
            }
        }

        return ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * @return array{key: string, label: string, required: bool, prompt: string, hint: string}|null
     */
    public static function definitionFor(string $key, ?string $profileKey = null): ?array
    {
        foreach (self::definitions() as $definition) {
            if ($definition['key'] === $key) {
                return self::tailorDefinition($definition, $profileKey ?? 'general');
            }
        }

        return null;
    }

    /**
     * @return array{key: string, label: string, required: bool, prompt: string, hint: string}
     */
    private static function definition(string $key, bool $required): array
    {
        return match ($key) {
            'project_name' => [
                'key' => 'project_name',
                'label' => __('Project name'),
                'required' => $required,
                'prompt' => __('What should this project be called?'),
                'hint' => __('A short title for dashboards — e.g. “Customer portal v2”, “Leadership workshop”, “Q3 compliance training”.'),
            ],
            'objective' => [
                'key' => 'objective',
                'label' => __('Objective'),
                'required' => $required,
                'prompt' => __('What outcome should this project achieve?'),
                'hint' => __('The main result, who it serves, and by when — in one or two sentences.'),
            ],
            'work_items' => [
                'key' => 'work_items',
                'label' => __('Scope & deliverables'),
                'required' => $required,
                'prompt' => __('What are the main work items or deliverables?'),
                'hint' => __('List at least two concrete bullets. Each becomes a task — features, sessions, modules, milestones, or artifacts.'),
            ],
            'timeline' => [
                'key' => 'timeline',
                'label' => __('Timeline'),
                'required' => $required,
                'prompt' => __('What is the target timeline or key milestones?'),
                'hint' => __('Dates, phases, or relative timing — e.g. “pilot in 6 weeks”, “Q3 launch”, “two-day workshop in May”.'),
            ],
            'success_criteria' => [
                'key' => 'success_criteria',
                'label' => __('Success criteria'),
                'required' => $required,
                'prompt' => __('How will you know this project succeeded?'),
                'hint' => __('Metrics, acceptance tests, completion rates, feedback scores, or sign-off conditions.'),
            ],
            'decisions' => [
                'key' => 'decisions',
                'label' => __('Key decisions'),
                'required' => $required,
                'prompt' => __('Which approvals or decisions are still needed?'),
                'hint' => __('Budget, vendor choice, scope trade-offs, go/no-go gates, or policy approvals.'),
            ],
            'reminders' => [
                'key' => 'reminders',
                'label' => __('Check-ins'),
                'required' => $required,
                'prompt' => __('Are there recurring check-ins or reminders to plan?'),
                'hint' => __('Standups, steering reviews, rehearsal dates, or milestone reminders.'),
            ],
            default => throw new \InvalidArgumentException("Unknown requirement key [{$key}]"),
        };
    }

    /**
     * @param  array{key: string, label: string, required: bool, prompt: string, hint: string}  $definition
     * @return array{key: string, label: string, required: bool, prompt: string, hint: string}
     */
    private static function tailorDefinition(array $definition, string $profileKey): array
    {
        if ($definition['key'] !== 'work_items') {
            return $definition;
        }

        $tailoredHint = match ($profileKey) {
            'software' => __('List features, integrations, environments, testing, or release steps — at least two bullets.'),
            'training' => __('List modules, materials, assessments, pilot sessions, or rollout steps — at least two bullets.'),
            'workshop' => __('List agenda blocks, prep work, facilitation tasks, or outputs — at least two bullets.'),
            'event' => __('List venue, program, vendors, communications, or run-of-show tasks — at least two bullets.'),
            'operations' => __('List process changes, communications, training, or rollout tasks — at least two bullets.'),
            default => $definition['hint'],
        };

        return [...$definition, 'hint' => $tailoredHint];
    }
}
