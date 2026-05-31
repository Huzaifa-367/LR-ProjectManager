<?php

namespace App\Support;

use App\Enums\TaskKind;
use App\Models\MemberDailyFocus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class CommandCentreStats
{
    public function __construct(
        private readonly TaskVisibilityScope $taskVisibility,
        private readonly ProjectVisibilityScope $projectVisibility,
    ) {}

    /**
     * @return array{
     *     active_focus: int,
     *     open_tasks: int,
     *     projects: int,
     *     done_today: int
     * }
     */
    public function forMember(
        Organization $organization,
        OrganizationMember $member,
        ?CarbonInterface $focusDate = null,
    ): array {
        $focusDate ??= now()->startOfDay();
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $activeFocus = MemberDailyFocus::query()
            ->where('organization_member_id', $member->id)
            ->whereDate('focus_date', $focusDate)
            ->whereHas('task', fn (Builder $tasks) => $tasks->where('is_done', false))
            ->count();

        $openTasks = Task::query()
            ->forOrganization($organization->id)
            ->ofKind(TaskKind::Task)
            ->where('is_done', false)
            ->tap(fn (Builder $query) => $this->taskVisibility->apply($query, $member))
            ->count();

        $projects = Project::query()
            ->where('organization_id', $organization->id)
            ->active()
            ->tap(fn (Builder $query) => $this->projectVisibility->apply($query, $member))
            ->count();

        $doneToday = Task::query()
            ->forOrganization($organization->id)
            ->ofKind(TaskKind::Task)
            ->where('is_done', true)
            ->whereBetween('completed_at', [$todayStart, $todayEnd])
            ->tap(fn (Builder $query) => $this->taskVisibility->apply($query, $member))
            ->count();

        return [
            'active_focus' => $activeFocus,
            'open_tasks' => $openTasks,
            'projects' => $projects,
            'done_today' => $doneToday,
        ];
    }
}
