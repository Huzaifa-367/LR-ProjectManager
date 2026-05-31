import ActivityLogController from '@/actions/App/Http/Controllers/ActivityLogController';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationMailProfileController from '@/actions/App/Http/Controllers/OrganizationMailProfileController';
import OrganizationReportsController from '@/actions/App/Http/Controllers/OrganizationReportsController';
import NotificationPreferenceController from '@/actions/App/Http/Controllers/NotificationPreferenceController';
import OrganizationMemberController from '@/actions/App/Http/Controllers/OrganizationMemberController';
import OrganizationRoleController from '@/actions/App/Http/Controllers/OrganizationRoleController';
import { canOrg } from '@/hooks/use-org-permissions';
import type { CommandCentrePermissions } from '@/types/organization';

export type OrganizationSettingsNavItem = {
    label: string;
    href: string;
    active?: boolean;
};

export function buildOrganizationSettingsNav(
    organizationId: number,
    permissions: CommandCentrePermissions,
    activeHref: string,
): OrganizationSettingsNavItem[] {
    const items: Array<{ label: string; href: string; visible: boolean }> = [
        {
            label: 'General',
            href: OrganizationController.show.url(organizationId),
            visible: true,
        },
        {
            label: 'Members',
            href: OrganizationMemberController.index.url(organizationId),
            visible: canOrg(permissions.org, 'org.members.index'),
        },
        {
            label: 'Roles',
            href: OrganizationRoleController.index.url(organizationId),
            visible: canOrg(permissions.org, 'org.roles.index'),
        },
        {
            label: 'Activity',
            href: ActivityLogController.index.url(organizationId),
            visible: canOrg(permissions.org, 'org.activity-logs.index'),
        },
        {
            label: 'Mail',
            href: OrganizationMailProfileController.index.url(organizationId),
            visible:
                canOrg(permissions.org, 'org.mail-profiles.index') ||
                canOrg(permissions.org, 'org.member-mail-linkage.show'),
        },
        {
            label: 'Notifications',
            href: NotificationPreferenceController.show.url(organizationId),
            visible: canOrg(
                permissions.org,
                'org.notification-preferences.show',
            ),
        },
        {
            label: 'Reports',
            href: OrganizationReportsController.index.url(organizationId),
            visible: canOrg(
                permissions.org,
                'org.notification-deliveries.index',
            ),
        },
    ];

    const generalHref = OrganizationController.show.url(organizationId);

    return items
        .filter((item) => item.visible)
        .map(({ label, href }) => ({
            label,
            href,
            active: isOrganizationSettingsNavActive(
                href,
                activeHref,
                generalHref,
            ),
        }));
}

function isOrganizationSettingsNavActive(
    itemHref: string,
    activeHref: string,
    generalHref: string,
): boolean {
    if (activeHref === itemHref) {
        return true;
    }

    if (itemHref === generalHref) {
        return false;
    }

    return activeHref.startsWith(`${itemHref}/`);
}
