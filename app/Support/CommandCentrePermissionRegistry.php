<?php

namespace App\Support;

final class CommandCentrePermissionRegistry
{
    /**
     * @return array<string, array{label: string, description?: string, permissions: array<string, string>}>
     */
    public static function orgGroups(): array
    {
        return [
            'organization' => [
                'label' => 'Organization',
                'permissions' => [
                    'org.organizations.show' => 'View organization profile',
                    'org.organizations.update' => 'Update organization profile & settings',
                    'org.organizations.destroy' => 'Delete organization',
                ],
            ],
            'members' => [
                'label' => 'Members',
                'permissions' => [
                    'org.members.index' => 'List members',
                    'org.members.store' => 'Invite / add member',
                    'org.members.show' => 'View member detail',
                    'org.members.update' => 'Update member (role, title)',
                    'org.members.disable' => 'Disable member',
                ],
            ],
            'org_roles' => [
                'label' => 'Organization roles',
                'permissions' => [
                    'org.roles.index' => 'List org roles',
                    'org.roles.store' => 'Create org role',
                    'org.roles.show' => 'View org role',
                    'org.roles.update' => 'Update org role name/description',
                    'org.roles.destroy' => 'Delete custom org role',
                    'org.roles.permissions.sync' => 'Edit org role permission matrix',
                ],
            ],
            'command_centre' => [
                'label' => 'Command centre',
                'permissions' => [
                    'org.command-centre.index' => 'Open command centre dashboard',
                ],
            ],
            'projects' => [
                'label' => 'Projects (org routes)',
                'permissions' => [
                    'org.projects.index' => 'List projects',
                    'org.projects.store' => 'Create project',
                    'org.projects.show' => 'View project summary',
                    'org.projects.update' => 'Update project metadata',
                    'org.projects.archive' => 'Archive project',
                    'org.projects.scope.all' => 'See all org projects in lists',
                    'org.projects.scope.member' => 'See only projects where on team',
                ],
            ],
            'tasks' => [
                'label' => 'Tasks (org routes)',
                'permissions' => [
                    'org.tasks.index' => 'List tasks (all projects, filtered by scope)',
                    'org.tasks.store' => 'Create task',
                    'org.tasks.show' => 'View task detail',
                    'org.tasks.update' => 'Update task fields',
                    'org.tasks.destroy' => 'Delete task',
                    'org.tasks.status.update' => 'Change task status',
                    'org.tasks.assignees.sync' => 'Set task assignees',
                    'org.tasks.toggle-done' => 'Mark task done / undone',
                    'org.tasks.scope.all' => 'See all tasks in visible projects',
                    'org.tasks.scope.own' => 'See only assigned or created tasks',
                ],
            ],
            'reminders' => [
                'label' => 'Reminders',
                'permissions' => [
                    'org.reminders.index' => 'List reminders',
                    'org.reminders.store' => 'Create reminder',
                    'org.reminders.update' => 'Update reminder',
                    'org.reminders.destroy' => 'Delete reminder',
                ],
            ],
            'focus' => [
                'label' => 'Daily focus',
                'permissions' => [
                    'org.focus.index' => 'View own daily focus list',
                    'org.focus.store' => 'Pin task to daily focus',
                    'org.focus.reorder' => 'Reorder daily focus',
                    'org.focus.destroy' => 'Unpin from daily focus',
                ],
            ],
            'notes' => [
                'label' => 'Personal notes',
                'permissions' => [
                    'org.notes.index' => 'List own notes',
                    'org.notes.store' => 'Create note',
                    'org.notes.update' => 'Update own note',
                    'org.notes.destroy' => 'Delete own note',
                ],
            ],
            'invitations' => [
                'label' => 'Invitations',
                'permissions' => [
                    'org.invitations.index' => 'List pending invitations',
                    'org.invitations.store' => 'Send invitation',
                    'org.invitations.destroy' => 'Revoke invitation',
                    'org.invitations.resend' => 'Resend invitation email',
                ],
            ],
            'mail_profiles' => [
                'label' => 'Mail profiles (SMTP / Gmail)',
                'permissions' => [
                    'org.mail-profiles.index' => 'List mail profiles',
                    'org.mail-profiles.store' => 'Create mail profile',
                    'org.mail-profiles.show' => 'View mail profile',
                    'org.mail-profiles.update' => 'Update mail profile',
                    'org.mail-profiles.destroy' => 'Delete mail profile',
                    'org.mail-profiles.test' => 'Send test email',
                    'org.mail-profiles.oauth.callback' => 'Complete Gmail OAuth',
                ],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'permissions' => [
                    'org.notifications.index' => 'List in-app notifications',
                    'org.notifications.mark-read' => 'Mark notifications read',
                    'org.notification-preferences.show' => 'View own notification preferences',
                    'org.notification-preferences.update' => 'Update own notification preferences',
                    'org.member-mail-linkage.show' => 'View personal Gmail linkage',
                    'org.member-mail-linkage.update' => 'Connect personal Gmail with app pin',
                    'org.member-mail-linkage.test' => 'Verify personal Gmail linkage',
                    'org.notification-deliveries.index' => 'View outbound mail log (admin)',
                ],
            ],
            'activity_logs' => [
                'label' => 'Activity log',
                'permissions' => [
                    'org.activity-logs.index' => 'View organization audit log',
                ],
            ],
            'task_comments' => [
                'label' => 'Task comments (org routes)',
                'permissions' => [
                    'org.task-comments.index' => 'List comments on a task',
                    'org.task-comments.store' => 'Add comment',
                    'org.task-comments.update' => 'Edit own comment',
                    'org.task-comments.destroy' => 'Delete own comment',
                ],
            ],
            'attachments' => [
                'label' => 'Attachments (org routes)',
                'permissions' => [
                    'org.attachments.store' => 'Upload attachment',
                    'org.attachments.destroy' => 'Delete attachment',
                ],
            ],
            'exports' => [
                'label' => 'Data exports',
                'permissions' => [
                    'org.exports.store' => 'Request async export',
                    'org.exports.show' => 'Download completed export',
                ],
            ],
            'ai_onboarding' => [
                'label' => 'AI onboarding & assistant',
                'permissions' => [
                    'org.ai-sessions.index' => 'List own AI sessions',
                    'org.ai-sessions.show' => 'View AI session transcript',
                    'org.ai-sessions.store' => 'Start AI session',
                    'org.ai-onboarding.start' => 'Open project onboarding wizard',
                    'org.ai-onboarding.propose' => 'Generate AI project plan',
                    'org.ai-onboarding.show' => 'View onboarding proposal',
                    'org.ai-onboarding.update' => 'Edit proposal payload before apply',
                    'org.ai-onboarding.approve' => 'Approve proposal',
                    'org.ai-onboarding.apply' => 'Apply approved proposal (creates project + tasks)',
                    'org.ai-onboarding.reject' => 'Reject proposal',
                    'org.ai-assist.store' => 'Chat with org-scoped assistant',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public static function projectGroups(): array
    {
        return [
            'project_members' => [
                'label' => 'Project members',
                'permissions' => [
                    'project.members.index' => 'List project team',
                    'project.members.store' => 'Add project member',
                    'project.members.update' => 'Update project member role',
                    'project.members.destroy' => 'Remove project member',
                ],
            ],
            'project_roles' => [
                'label' => 'Project roles',
                'permissions' => [
                    'project.roles.index' => 'List project roles',
                    'project.roles.store' => 'Create project role',
                    'project.roles.show' => 'View project role',
                    'project.roles.update' => 'Update project role',
                    'project.roles.destroy' => 'Delete project role',
                    'project.roles.permissions.sync' => 'Edit project role permissions',
                ],
            ],
            'project_tasks' => [
                'label' => 'Project tasks',
                'permissions' => [
                    'project.tasks.index' => 'List project tasks',
                    'project.tasks.store' => 'Create project task',
                    'project.tasks.show' => 'View project task',
                    'project.tasks.update' => 'Update project task',
                    'project.tasks.destroy' => 'Delete project task',
                    'project.tasks.status.update' => 'Change project task status',
                    'project.tasks.assignees.sync' => 'Set project task assignees',
                    'project.tasks.toggle-done' => 'Mark project task done',
                    'project.tasks.scope.all' => 'See all project tasks',
                    'project.tasks.scope.own' => 'See only own project tasks',
                ],
            ],
            'project_decisions' => [
                'label' => 'Project decisions',
                'permissions' => [
                    'project.decisions.index' => 'List project decisions',
                    'project.decisions.store' => 'Create project decision',
                    'project.decisions.update' => 'Update project decision',
                    'project.decisions.destroy' => 'Delete project decision',
                ],
            ],
            'project_task_comments' => [
                'label' => 'Project task comments',
                'permissions' => [
                    'project.task-comments.index' => 'List task comments',
                    'project.task-comments.store' => 'Add task comment',
                    'project.task-comments.update' => 'Edit own task comment',
                    'project.task-comments.destroy' => 'Delete own task comment',
                ],
            ],
            'project_attachments' => [
                'label' => 'Project attachments',
                'permissions' => [
                    'project.attachments.store' => 'Upload project attachment',
                    'project.attachments.destroy' => 'Delete project attachment',
                ],
            ],
            'project_ai' => [
                'label' => 'Project AI',
                'permissions' => [
                    'project.ai-assist.store' => 'Use project AI assist',
                    'project.ai-onboarding.propose' => 'Generate project onboarding proposal',
                ],
            ],
        ];
    }

    /** @return list<string> */
    public static function allOrgSlugs(): array
    {
        return collect(self::orgGroups())
            ->flatMap(fn (array $group): array => array_keys($group['permissions']))
            ->values()
            ->all();
    }

    /** @return list<string> */
    public static function allProjectSlugs(): array
    {
        return collect(self::projectGroups())
            ->flatMap(fn (array $group): array => array_keys($group['permissions']))
            ->values()
            ->all();
    }

    /** @return list<string> */
    public static function orgSlugsExcept(string ...$exclude): array
    {
        return array_values(array_diff(self::allOrgSlugs(), $exclude));
    }

    /** @return list<string> */
    public static function orgSlugsMatchingPrefix(string $prefix): array
    {
        return array_values(array_filter(
            self::allOrgSlugs(),
            fn (string $slug): bool => str_starts_with($slug, $prefix),
        ));
    }
}
