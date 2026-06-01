<?php

namespace App\Support;

final class CommandCentreRoleTemplateRegistry
{
    /**
     * @return list<array{slug: string, name: string, is_system: bool, sort_order: int, permissions: list<string>}>
     */
    public static function orgRoles(): array
    {
        return [
            [
                'slug' => 'owner',
                'name' => 'Owner',
                'is_system' => true,
                'sort_order' => 1,
                'permissions' => CommandCentrePermissionRegistry::allOrgSlugs(),
            ],
            [
                'slug' => 'admin',
                'name' => 'Admin',
                'is_system' => true,
                'sort_order' => 2,
                'permissions' => CommandCentrePermissionRegistry::orgSlugsExcept('org.organizations.destroy'),
            ],
            [
                'slug' => 'lead',
                'name' => 'Lead',
                'is_system' => true,
                'sort_order' => 3,
                'permissions' => self::leadOrgPermissions(),
            ],
            [
                'slug' => 'member',
                'name' => 'Member',
                'is_system' => true,
                'sort_order' => 4,
                'permissions' => self::memberOrgPermissions(),
            ],
            [
                'slug' => 'viewer',
                'name' => 'Viewer',
                'is_system' => true,
                'sort_order' => 5,
                'permissions' => self::viewerOrgPermissions(),
            ],
        ];
    }

    /**
     * @return list<array{slug: string, name: string, is_default: bool, sort_order: int, permissions: list<string>}>
     */
    public static function projectRoles(): array
    {
        return [
            [
                'slug' => 'project_owner',
                'name' => 'Project owner',
                'is_default' => true,
                'sort_order' => 1,
                'permissions' => CommandCentrePermissionRegistry::allProjectSlugs(),
            ],
            [
                'slug' => 'project_lead',
                'name' => 'Project lead',
                'is_default' => false,
                'sort_order' => 2,
                'permissions' => self::projectLeadPermissions(),
            ],
            [
                'slug' => 'contributor',
                'name' => 'Contributor',
                'is_default' => false,
                'sort_order' => 3,
                'permissions' => self::contributorProjectPermissions(),
            ],
            [
                'slug' => 'project_viewer',
                'name' => 'Project viewer',
                'is_default' => false,
                'sort_order' => 4,
                'permissions' => self::projectViewerPermissions(),
            ],
        ];
    }

    /** @return list<string> */
    private static function leadOrgPermissions(): array
    {
        $prefixes = [
            'org.organizations.show',
            'org.members.',
            'org.invitations.',
            'org.roles.',
            'org.command-centre.index',
            'org.projects.',
            'org.ai-',
            'org.tasks.',
            'org.reminders.',
            'org.focus.',
            'org.notes.',
            'org.task-comments.',
            'org.attachments.',
            'org.activity-logs.index',
            'org.notifications.',
            'org.notification-preferences.',
            'org.member-mail-linkage.',
            'org.exports.',
        ];

        return self::filterOrgSlugsByPrefixes($prefixes);
    }

    /** @return list<string> */
    private static function memberOrgPermissions(): array
    {
        return [
            'org.command-centre.index',
            'org.projects.index',
            'org.projects.scope.member',
            'org.projects.show',
            'org.tasks.index',
            'org.tasks.store',
            'org.tasks.show',
            'org.tasks.update',
            'org.tasks.status.update',
            'org.tasks.toggle-done',
            'org.tasks.scope.own',
            'org.tasks.assignees.sync',
            'org.focus.index',
            'org.focus.store',
            'org.focus.reorder',
            'org.focus.destroy',
            'org.notes.index',
            'org.notes.store',
            'org.notes.update',
            'org.notes.destroy',
            'org.task-comments.index',
            'org.task-comments.store',
            'org.task-comments.update',
            'org.task-comments.destroy',
            'org.attachments.store',
            'org.notifications.index',
            'org.notifications.mark-read',
            'org.notification-preferences.show',
            'org.notification-preferences.update',
            'org.member-mail-linkage.show',
            'org.member-mail-linkage.update',
            'org.member-mail-linkage.test',
            'org.exports.store',
            'org.exports.show',
        ];
    }

    /** @return list<string> */
    private static function viewerOrgPermissions(): array
    {
        return [
            'org.command-centre.index',
            'org.projects.index',
            'org.projects.scope.member',
            'org.projects.show',
            'org.tasks.index',
            'org.tasks.show',
            'org.tasks.scope.own',
            'org.focus.index',
            'org.notes.index',
        ];
    }

    /** @return list<string> */
    private static function projectLeadPermissions(): array
    {
        return array_values(array_diff(
            CommandCentrePermissionRegistry::allProjectSlugs(),
            ['project.roles.destroy'],
        ));
    }

    /** @return list<string> */
    private static function contributorProjectPermissions(): array
    {
        return [
            'project.members.index',
            'project.tasks.index',
            'project.tasks.store',
            'project.tasks.show',
            'project.tasks.update',
            'project.tasks.status.update',
            'project.tasks.toggle-done',
            'project.tasks.scope.own',
            'project.tasks.assignees.sync',
            'project.decisions.index',
            'project.ai-assist.store',
            'project.task-comments.index',
            'project.task-comments.store',
            'project.task-comments.update',
            'project.task-comments.destroy',
            'project.attachments.store',
        ];
    }

    /** @return list<string> */
    private static function projectViewerPermissions(): array
    {
        return [
            'project.members.index',
            'project.tasks.index',
            'project.tasks.show',
            'project.tasks.scope.own',
            'project.decisions.index',
        ];
    }

    /**
     * @param  list<string>  $prefixes
     * @return list<string>
     */
    private static function filterOrgSlugsByPrefixes(array $prefixes): array
    {
        return array_values(array_filter(
            CommandCentrePermissionRegistry::allOrgSlugs(),
            function (string $slug) use ($prefixes): bool {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($slug, $prefix)) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }
}
