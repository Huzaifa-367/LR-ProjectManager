<?php

namespace App\Rules;

use App\Models\MemberDailyFocus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FocusCapNotExceeded implements ValidationRule
{
    public function __construct(
        private readonly Organization $organization,
        private readonly OrganizationMember $member,
        private readonly ?string $focusDate = null,
        private readonly ?int $taskId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $focusDate = $this->focusDate ?? now()->toDateString();

        if ($this->taskId !== null) {
            $alreadyPinned = MemberDailyFocus::query()
                ->where('organization_member_id', $this->member->id)
                ->where('task_id', $this->taskId)
                ->whereDate('focus_date', $focusDate)
                ->exists();

            if ($alreadyPinned) {
                return;
            }
        }

        $settings = $this->organization->settings ?? Organization::defaultSettings();
        $focusCap = (int) ($settings['focus_cap'] ?? 10);

        $activeCount = MemberDailyFocus::query()
            ->where('organization_member_id', $this->member->id)
            ->whereDate('focus_date', $focusDate)
            ->whereHas('task', fn ($query) => $query->where('is_done', false))
            ->count();

        if ($activeCount >= $focusCap) {
            $fail(__('Daily focus cap of :cap items reached.', ['cap' => $focusCap]));
        }
    }
}
