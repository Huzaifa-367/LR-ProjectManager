import { Link } from '@inertiajs/react';
import { Building2, CheckSquare, FolderKanban, LayoutGrid, Settings, ShieldCheck } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import type { NavMainSection } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useOrganizationContext } from '@/hooks/use-organization-context';
import { canOrg, canOrgAny } from '@/hooks/use-org-permissions';
import { usePermissions } from '@/hooks/use-permissions';
import { index as organizationsIndex } from '@/routes/organizations';
import { index as rolesIndex } from '@/routes/roles';
import type { NavItem } from '@/types';

type SidebarNavItem = NavItem & {
    permission?: string | string[];
    orgPermission?: string | string[];
};

function isNavItemVisible(
    item: SidebarNavItem,
    can: (permission: string) => boolean,
    canAny: (...permissions: string[]) => boolean,
    canOrgNav: (permission: string) => boolean,
    canOrgNavAny: (...permissions: string[]) => boolean,
): boolean {
    const orgPermission = item.orgPermission;

    if (orgPermission) {
        if (Array.isArray(orgPermission)) {
            return canOrgNavAny(...orgPermission);
        }

        return canOrgNav(orgPermission);
    }

    const permission = item.permission;

    if (!permission) {
        return true;
    }

    if (Array.isArray(permission)) {
        return canAny(...permission);
    }

    return can(permission);
}

export function AppSidebar() {
    const { can, canAny } = usePermissions();
    const { selectedOrganization, permissions: orgPermissions } =
        useOrganizationContext();

    const canOrgNav = (permission: string): boolean =>
        orgPermissions ? canOrg(orgPermissions.org, permission) : false;

    const canOrgNavAny = (...permissionList: string[]): boolean =>
        orgPermissions ? canOrgAny(orgPermissions.org, ...permissionList) : false;

    const commandCentreHref =
        selectedOrganization !== null
            ? `/organizations/${selectedOrganization.id}/command-centre`
            : organizationsIndex.url();

    const projectsHref =
        selectedOrganization !== null
            ? `/organizations/${selectedOrganization.id}/projects`
            : organizationsIndex.url();

    const tasksHref =
        selectedOrganization !== null
            ? `/organizations/${selectedOrganization.id}/tasks`
            : organizationsIndex.url();

    const settingsHref =
        selectedOrganization !== null
            ? `/organizations/${selectedOrganization.id}`
            : organizationsIndex.url();

    const mainNavSections: { label: string; items: SidebarNavItem[] }[] = [
        {
            label: 'Overview',
            items: [
                {
                    title: 'Organizations',
                    href: organizationsIndex.url(),
                    icon: Building2,
                },
                {
                    title: 'Command Centre',
                    href: commandCentreHref,
                    icon: LayoutGrid,
                    orgPermission: 'org.command-centre.index',
                },
                {
                    title: 'Projects',
                    href: projectsHref,
                    icon: FolderKanban,
                    orgPermission: 'org.projects.index',
                },
                {
                    title: 'Tasks',
                    href: tasksHref,
                    icon: CheckSquare,
                    orgPermission: 'org.tasks.index',
                },
                {
                    title: 'Settings',
                    href: settingsHref,
                    icon: Settings,
                    orgPermission: 'org.organizations.show',
                },
            ],
        },
        {
            label: 'Administration',
            items: [
                {
                    title: 'Roles & Permissions',
                    href: rolesIndex(),
                    icon: ShieldCheck,
                    permission: 'roles.view',
                },
            ],
        },
    ];

    const sections: NavMainSection[] = mainNavSections
        .map((section) => ({
            label: section.label,
            items: section.items.filter((item) =>
                isNavItemVisible(
                    item,
                    can,
                    canAny,
                    canOrgNav,
                    canOrgNavAny,
                ),
            ),
        }))
        .filter((section) => section.items.length > 0);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={organizationsIndex.url()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain sections={sections} />
            </SidebarContent>
        </Sidebar>
    );
}
