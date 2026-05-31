<?php

namespace App\Support;

use App\Ai\Agents\ProjectOnboardingPlanAgent;
use Illuminate\Support\Facades\Config;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Responses\StructuredAgentResponse;

final class OnboardingPlanGenerator
{
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
    public function generate(string $brief, int $leadMemberId, string $profileKey = 'general'): array
    {
        if (! config('onboarding.ai.enabled', true)) {
            throw OnboardingPlanGenerationException::disabled();
        }

        if (! $this->isConfigured()) {
            throw OnboardingPlanGenerationException::notConfigured();
        }

        $plan = $this->generateFromAi($brief, $leadMemberId, $profileKey);

        if ($plan === null) {
            throw OnboardingPlanGenerationException::failed();
        }

        return $plan;
    }

    public function isConfigured(): bool
    {
        $provider = Config::get('ai.default', 'openai');
        $key = Config::get("ai.providers.{$provider}.key");

        return is_string($key) && trim($key) !== '';
    }

    /**
     * @return array{
     *     project_name: string,
     *     objective: string,
     *     next_action: string,
     *     tasks: list<array<string, mixed>>,
     *     decisions: list<array<string, mixed>>,
     *     reminders: list<array<string, mixed>>
     * }|null
     */
    private function generateFromAi(string $brief, int $leadMemberId, string $profileKey): ?array
    {
        $referenceDate = now()->timezone(config('app.timezone'));
        $todayContext = sprintf(
            "Today's date: %s (%s), timezone %s.",
            $referenceDate->format('Y-m-d'),
            $referenceDate->format('l'),
            $referenceDate->timezoneName,
        );

        try {
            $response = (new ProjectOnboardingPlanAgent($profileKey, $referenceDate))->prompt(
                "{$todayContext}\n\nCreate a project onboarding plan from this brief.\n\nBrief:\n---\n{$brief}\n---",
                provider: Config::get('ai.default', 'openai'),
                model: config('onboarding.ai.model'),
                timeout: config('onboarding.ai.timeout', 120),
            );
        } catch (RateLimitedException) {
            throw OnboardingPlanGenerationException::rateLimited();
        }

        if (! $response instanceof StructuredAgentResponse) {
            return null;
        }

        $plan = $this->normalizePlan($response->structured, $leadMemberId);

        return $plan === [] ? null : $plan;
    }

    /**
     * @param  array<string, mixed>  $structured
     * @return array{
     *     project_name: string,
     *     objective: string,
     *     next_action: string,
     *     tasks: list<array<string, mixed>>,
     *     decisions: list<array<string, mixed>>,
     *     reminders: list<array<string, mixed>>
     * }
     */
    private function normalizePlan(array $structured, int $leadMemberId): array
    {
        $tasks = [];

        foreach ($structured['tasks'] ?? [] as $task) {
            if (! is_array($task)) {
                continue;
            }

            $title = trim(Utf8::sanitize((string) ($task['title'] ?? '')));

            if ($title === '') {
                continue;
            }

            $tasks[] = [
                'title' => $title,
                'description' => trim(Utf8::sanitize((string) ($task['description'] ?? ''))),
                'priority' => $this->normalizePriority((string) ($task['priority'] ?? 'medium')),
                'status' => 'pending',
                'deadline_date' => $this->resolveDeadlineDate($task),
                'assignee_member_ids' => [$leadMemberId],
                'kind' => 'task',
            ];
        }

        $decisions = [];
        $sortOrder = 1;

        foreach ($structured['decisions'] ?? [] as $decision) {
            if (! is_array($decision)) {
                continue;
            }

            $title = trim(Utf8::sanitize((string) ($decision['title'] ?? '')));

            if ($title === '') {
                continue;
            }

            $decisions[] = [
                'title' => $title,
                'sort_order' => $sortOrder++,
                'assignee_member_ids' => [$leadMemberId],
            ];
        }

        $reminders = [];

        foreach ($structured['reminders'] ?? [] as $reminder) {
            if (! is_array($reminder)) {
                continue;
            }

            $title = trim(Utf8::sanitize((string) ($reminder['title'] ?? '')));

            if ($title === '') {
                continue;
            }

            $reminders[] = [
                'title' => $title,
                'description' => trim(Utf8::sanitize((string) ($reminder['description'] ?? ''))),
                'meta' => ['icon' => '📅', 'is_urgent' => false],
                'assignee_member_ids' => [$leadMemberId],
            ];
        }

        if ($tasks === []) {
            return [];
        }

        $projectName = trim(Utf8::sanitize((string) ($structured['project_name'] ?? '')));
        $objective = trim(Utf8::sanitize((string) ($structured['objective'] ?? '')));
        $nextAction = trim(Utf8::sanitize((string) ($structured['next_action'] ?? '')));

        return [
            'project_name' => $projectName !== '' ? $projectName : __('New strategic project'),
            'objective' => $objective,
            'next_action' => $nextAction !== '' ? $nextAction : (string) $tasks[0]['title'],
            'tasks' => array_slice($tasks, 0, (int) config('onboarding.max_tasks', 15)),
            'decisions' => $decisions,
            'reminders' => $reminders,
        ];
    }

    private function normalizePriority(string $priority): string
    {
        return in_array($priority, ['high', 'medium', 'low'], true) ? $priority : 'medium';
    }

    /**
     * @param  array<string, mixed>  $task
     */
    private function resolveDeadlineDate(array $task): ?string
    {
        $normalized = TaskDeadlineValue::normalize($task['deadline_date'] ?? null);

        if ($normalized !== null) {
            return $normalized;
        }

        return match ((string) ($task['deadline_type'] ?? 'none')) {
            'today' => now()->toDateTimeString(),
            'this_week' => now()->endOfWeek()->toDateTimeString(),
            default => null,
        };
    }
}
