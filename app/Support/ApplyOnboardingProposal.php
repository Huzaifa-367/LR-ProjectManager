<?php

namespace App\Support;

use App\Enums\AiSessionStatus;
use App\Enums\OnboardingProposalStatus;
use App\Enums\PriorityLevel;
use App\Enums\TaskKind;
use App\Enums\TaskStatus;
use App\Models\AiOnboardingProposal;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApplyOnboardingProposal
{
    public function __construct(
        private readonly ProjectBootstrapService $projectBootstrap,
    ) {}

    public function apply(AiOnboardingProposal $proposal, OrganizationMember $actor): Project
    {
        if ($proposal->status !== OnboardingProposalStatus::Approved) {
            throw ValidationException::withMessages([
                'proposal' => __('Proposal must be approved before applying.'),
            ]);
        }

        return DB::transaction(function () use ($proposal, $actor): Project {
            $payload = $proposal->payload;
            $projectInput = $payload['project'] ?? [];
            $team = $payload['team'] ?? [];

            $project = $this->projectBootstrap->create(
                $proposal->organization,
                $actor,
                [
                    'name' => $projectInput['name'] ?? __('Untitled project'),
                    'objective' => $projectInput['objective'] ?? '',
                    'progress_percent' => $projectInput['progress_percent'] ?? 0,
                    'next_action' => $projectInput['next_action'] ?? null,
                    'health' => $projectInput['health'] ?? 'active',
                ],
                $team,
            );

            $this->createTasksFromPayload($proposal, $project, $actor, $payload['tasks'] ?? [], TaskKind::Task);
            $this->createTasksFromPayload($proposal, $project, $actor, $payload['decisions'] ?? [], TaskKind::Decision);
            $this->createTasksFromPayload($proposal, $project, $actor, $payload['reminders'] ?? [], TaskKind::Reminder);

            $proposal->update([
                'status' => OnboardingProposalStatus::Applied,
                'project_id' => $project->id,
                'applied_at' => now(),
                'applied_by_member_id' => $actor->id,
            ]);

            $proposal->session?->update([
                'status' => AiSessionStatus::Completed,
                'project_id' => $project->id,
            ]);

            return $project->fresh(['roles.permissions', 'members.role']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $drafts
     */
    private function createTasksFromPayload(
        AiOnboardingProposal $proposal,
        Project $project,
        OrganizationMember $actor,
        array $drafts,
        TaskKind $defaultKind,
    ): void {
        foreach ($drafts as $draft) {
            $kind = isset($draft['kind'])
                ? TaskKind::tryFrom((string) $draft['kind']) ?? $defaultKind
                : $defaultKind;

            $task = Task::query()->create([
                'organization_id' => $proposal->organization_id,
                'project_id' => $project->id,
                'kind' => $kind,
                'title' => $draft['title'] ?? __('Untitled'),
                'description' => $draft['description'] ?? null,
                'created_by_member_id' => $actor->id,
                'priority' => isset($draft['priority'])
                    ? PriorityLevel::tryFrom((string) $draft['priority'])
                    : null,
                'status' => isset($draft['status'])
                    ? TaskStatus::tryFrom((string) $draft['status']) ?? TaskStatus::Pending
                    : TaskStatus::Pending,
                'deadline_date' => $draft['deadline_date'] ?? null,
                'meta' => $draft['meta'] ?? null,
                'sort_order' => $draft['sort_order'] ?? 0,
            ]);

            $this->syncAssignees($project, $task, $actor, $draft['assignee_member_ids'] ?? []);
        }
    }

    /**
     * @param  list<int>  $assigneeMemberIds
     */
    private function syncAssignees(
        Project $project,
        Task $task,
        OrganizationMember $actor,
        array $assigneeMemberIds,
    ): void {
        $syncPayload = [];

        foreach (array_values($assigneeMemberIds) as $index => $assigneeMemberId) {
            $memberId = (int) $assigneeMemberId;

            if (! $this->memberCanBeAssigned($project, $memberId)) {
                throw ValidationException::withMessages([
                    'assignee_member_ids' => __('Assignee :id is not on the project team.', ['id' => $memberId]),
                ]);
            }

            $syncPayload[$memberId] = [
                'is_primary' => $index === 0,
                'assigned_at' => now(),
                'assigned_by_member_id' => $actor->id,
            ];
        }

        if ($syncPayload !== []) {
            $task->assignees()->sync($syncPayload);
        }
    }

    private function memberCanBeAssigned(Project $project, int $memberId): bool
    {
        return ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('organization_member_id', $memberId)
            ->exists();
    }
}
