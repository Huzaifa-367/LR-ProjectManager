import { Link } from '@inertiajs/react';
import { LayoutGrid, ShieldCheck } from 'lucide-react';
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
import { usePermissions } from '@/hooks/use-permissions';
import { home } from '@/routes';
import { index as rolesIndex } from '@/routes/roles';
import type { NavItem } from '@/types';

type SidebarNavItem = NavItem & {
    permission?: string | string[];
};

const mainNavSections: { label: string; items: SidebarNavItem[] }[] = [
    {
        label: 'Overview',
        items: [
            {
                title: 'Home',
                href: home(),
                icon: LayoutGrid,
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

function isNavItemVisible(
    item: SidebarNavItem,
    can: (permission: string) => boolean,
    canAny: (...permissions: string[]) => boolean,
): boolean {
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

    const sections: NavMainSection[] = mainNavSections
        .map((section) => ({
            label: section.label,
            items: section.items.filter((item) =>
                isNavItemVisible(item, can, canAny),
            ),
        }))
        .filter((section) => section.items.length > 0);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={home()} prefetch>
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
