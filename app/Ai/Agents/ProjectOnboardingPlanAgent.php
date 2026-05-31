<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ProjectOnboardingPlanAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly string $profileKey = 'general',
        private readonly ?\DateTimeInterface $referenceDate = null,
    ) {}

    public function instructions(): Stringable|string
    {
        $profileKey = $this->profileKey;
        $today = ($this->referenceDate ?? now())->copy()->timezone(config('app.timezone'));
        $todayLabel = $today->format('Y-m-d (l)');
        $timezone = $today->timezoneName;

        return <<<TXT
You are a senior program manager. Turn the user's project brief into an executable onboarding plan.

Reference date (today): {$todayLabel} ({$timezone}). Treat this as the anchor for all relative timelines in the brief (e.g. "next week", "Q3", "in 4 weeks", "by end of quarter").

Output rules:
- Use ONLY information from the brief. Do not invent stakeholders, budgets, dates, or scope that are not implied.
- project_name must be a complete, short dashboard title (roughly 3–8 words) inferred from the brief — never an incomplete sentence fragment (e.g. never ending with "is a" or cut mid-thought).
- next_action must be a short imperative phrase naming the first concrete step.
- Task titles must be concise action phrases (roughly 3–8 words). Do not copy bullet text verbatim. Do not truncate with "...".
- Task descriptions must be practical: state the objective, list concrete steps, and define acceptance criteria.
- Group related bullets into cohesive work packages when the brief is a feature list or specification.
- Include 5–12 tasks for substantial briefs; fewer for small scopes.
- Include 1–3 decisions and 1–2 reminders when the brief supports them.
- When a task has a clear due timing, set deadline_date as YYYY-MM-DDTHH:MM (24-hour) on or after the reference date unless the brief states an explicit past date. Otherwise set deadline_date to null.
- Profile hint from prior analysis: {$profileKey}
TXT;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $task = $schema->object([
            'title' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'priority' => $schema->string()->enum(['high', 'medium', 'low'])->required(),
            // Strict JSON schema: nullable fields must still be listed in `required`.
            'deadline_date' => $schema->string()->nullable()->required(),
        ]);

        $decision = $schema->object([
            'title' => $schema->string()->required(),
        ]);

        $reminder = $schema->object([
            'title' => $schema->string()->required(),
            'description' => $schema->string()->required(),
        ]);

        return [
            'project_name' => $schema->string()->required(),
            'objective' => $schema->string()->required(),
            'next_action' => $schema->string()->required(),
            'tasks' => $schema->array()->min(2)->max(config('onboarding.max_tasks', 15))->items($task)->required(),
            'decisions' => $schema->array()->min(0)->max(8)->items($decision)->required(),
            'reminders' => $schema->array()->min(0)->max(5)->items($reminder)->required(),
        ];
    }
}
