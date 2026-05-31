<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        int $organizationId,
        ?int $actorMemberId,
        Model $subject,
        string $event,
        array $properties = [],
    ): ActivityLog {
        return ActivityLog::query()->create([
            'organization_id' => $organizationId,
            'actor_member_id' => $actorMemberId,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'event' => $event,
            'properties' => $properties === [] ? null : $properties,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function logForAuthenticatedUser(
        Model $subject,
        string $event,
        array $properties = [],
    ): ?ActivityLog {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $organizationId = $this->resolveOrganizationId($subject);

        if ($organizationId === null) {
            return null;
        }

        $organization = Organization::query()->find($organizationId);

        if ($organization === null) {
            return null;
        }

        $actor = app(OrganizationMemberResolver::class)->resolveForOrganization($user, $organization);

        return $this->log(
            $organizationId,
            $actor?->id,
            $subject,
            $event,
            $properties,
        );
    }

    private function resolveOrganizationId(Model $subject): ?int
    {
        if (isset($subject->organization_id)) {
            return (int) $subject->organization_id;
        }

        if ($subject instanceof OrganizationMember) {
            return (int) $subject->organization_id;
        }

        if ($subject instanceof Organization) {
            return (int) $subject->id;
        }

        return null;
    }
}
