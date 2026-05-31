<?php

namespace App\Http\Requests\CommandCentre;

use App\Models\Organization;
use App\Rules\FocusCapNotExceeded;
use App\Rules\FocusTaskMustBeVisibleToMember;
use App\Support\OrganizationMemberResolver;
use Illuminate\Foundation\Http\FormRequest;

class StoreFocusPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Organization $organization */
        $organization = $this->route('organization');

        $member = app(OrganizationMemberResolver::class)
            ->requireForOrganization($this->user(), $organization);

        $focusDate = $this->input('focus_date', now()->toDateString());
        $taskId = (int) $this->input('task_id');

        return [
            'task_id' => [
                'required',
                'integer',
                'exists:tasks,id',
                new FocusTaskMustBeVisibleToMember($organization, $member),
                new FocusCapNotExceeded($organization, $member, $focusDate, $taskId),
            ],
            'focus_date' => ['sometimes', 'date'],
        ];
    }
}
