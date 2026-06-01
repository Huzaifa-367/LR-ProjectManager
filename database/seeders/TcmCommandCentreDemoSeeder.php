<?php

namespace Database\Seeders;

use App\Enums\OrganizationMemberStatus;
use App\Enums\TaskKind;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\OrganizationRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\MemberNotificationPreferenceSeeder;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Database\Seeder;

class TcmCommandCentreDemoSeeder extends Seeder
{
    /**
     * @var list<array{name: string, email: string, org_role: string, project_role: string}>
     */
    private const TEAM = [
        ['name' => 'Huzaifa', 'email' => 'huzaifa@atomcamp.com', 'org_role' => 'admin', 'project_role' => 'project_lead'],
        ['name' => 'Rafay', 'email' => 'rafay@atomcamp.com', 'org_role' => 'member', 'project_role' => 'contributor'],
        ['name' => 'Naveed', 'email' => 'naveed@atomcamp.com', 'org_role' => 'member', 'project_role' => 'contributor'],
        ['name' => 'Sarmad', 'email' => 'sarmad@atomcamp.com', 'org_role' => 'member', 'project_role' => 'contributor'],
    ];

    public function run(): void
    {
        $owner = User::query()->where('email', 'admin@gmail.com')->firstOrFail();

        $organization = Organization::query()->where('slug', 'atomcamp')->first();

        if ($organization === null) {
            $organization = app(OrganizationBootstrapService::class)->create($owner, [
                'name' => 'atomCamp',
                'slug' => 'atomcamp',
            ]);
        }

        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();
        $teamMembers = [$ownerMember];

        foreach (self::TEAM as $person) {
            $user = User::query()->where('email', $person['email'])->firstOrFail();
            $teamMembers[] = $this->ensureOrganizationMember(
                $organization,
                $user,
                $person['name'],
                $person['email'],
                $person['org_role'],
            );
        }

        $project = Project::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'Command Centre Rollout')
            ->first();

        if ($project === null) {
            // Only the creator is on the project team initially so "Add to team"
            // remains usable for assigning Huzaifa, Rafay, and the rest.
            $project = app(ProjectBootstrapService::class)->create(
                $organization,
                $ownerMember,
                [
                    'name' => 'Command Centre Rollout',
                    'objective' => 'Ship the unified command centre dashboard for atomCamp.',
                    'progress_percent' => 35,
                    'next_action' => 'Complete milestone 7 parity',
                ],
            );
        }

        if ($project->tasks()->exists()) {
            return;
        }

        $tasks = [
            ['title' => 'Finalize RBAC matrix review', 'days' => 2],
            ['title' => 'Import prototype task samples', 'days' => 5],
            ['title' => 'Validate focus pin cap behaviour', 'days' => 1],
        ];

        foreach ($tasks as $index => $task) {
            Task::query()->create([
                'organization_id' => $organization->id,
                'project_id' => $project->id,
                'kind' => TaskKind::Task,
                'title' => $task['title'],
                'created_by_member_id' => $ownerMember->id,
                'status' => 'pending',
                'deadline_date' => now()->addDays($task['days'])->setTime(17, 0),
            ]);
        }
    }

    private function ensureOrganizationMember(
        Organization $organization,
        User $user,
        string $displayName,
        string $email,
        string $orgRoleSlug,
    ): OrganizationMember {
        $existing = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $role = OrganizationRole::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $orgRoleSlug)
            ->firstOrFail();

        $member = OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'organization_role_id' => $role->id,
            'display_name' => $displayName,
            'email' => $email,
            'status' => OrganizationMemberStatus::Active->value,
            'joined_at' => now(),
        ]);

        app(MemberNotificationPreferenceSeeder::class)->seedForMember($member);

        return $member;
    }
}
