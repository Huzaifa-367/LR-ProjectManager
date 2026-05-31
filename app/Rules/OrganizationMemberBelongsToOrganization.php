<?php

namespace App\Rules;

use App\Models\Organization;
use App\Models\OrganizationMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OrganizationMemberBelongsToOrganization implements ValidationRule
{
    public function __construct(
        private readonly Organization $organization,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = OrganizationMember::query()
            ->where('organization_id', $this->organization->id)
            ->whereKey($value)
            ->exists();

        if (! $exists) {
            $fail(__('The selected member does not belong to this organization.'));
        }
    }
}
