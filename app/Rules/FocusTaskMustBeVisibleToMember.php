<?php

namespace App\Rules;

use App\Enums\TaskKind;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Task;
use App\Support\TaskVisibilityScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FocusTaskMustBeVisibleToMember implements ValidationRule
{
    public function __construct(
        private readonly Organization $organization,
        private readonly OrganizationMember $member,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $task = Task::query()
            ->where('organization_id', $this->organization->id)
            ->whereKey($value)
            ->first();

        if ($task === null || $task->kind !== TaskKind::Task) {
            $fail(__('The selected task is invalid for focus.'));

            return;
        }

        $visible = Task::query()
            ->whereKey($task->id)
            ->tap(fn ($query) => app(TaskVisibilityScope::class)->apply($query, $this->member))
            ->exists();

        if (! $visible) {
            $fail(__('You cannot pin a task you cannot see.'));
        }
    }
}
