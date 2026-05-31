<?php

namespace Database\Seeders;

use App\Enums\TaskKind;
use App\Models\Task;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Database\Seeder;

class TcmCommandCentreDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'admin@gmail.com')->first()
            ?? User::factory()->create([
                'name' => 'Talha Demo',
                'email' => 'admin@gmail.com',
            ]);

        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'TCM Group',
        ]);

        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            [
                'name' => 'Command Centre Rollout',
                'objective' => 'Ship the unified command centre dashboard.',
                'progress_percent' => 35,
                'next_action' => 'Complete milestone 7 parity',
            ],
        );

        $tasks = [
            'Finalize RBAC matrix review',
            'Import prototype task samples',
            'Validate focus pin cap behaviour',
        ];

        foreach ($tasks as $title) {
            Task::query()->create([
                'organization_id' => $organization->id,
                'project_id' => $project->id,
                'kind' => TaskKind::Task,
                'title' => $title,
                'created_by_member_id' => $member->id,
                'status' => 'pending',
            ]);
        }
    }
}
