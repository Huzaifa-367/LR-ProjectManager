import { Breadcrumbs } from '@/components/breadcrumbs';
import { NavUser } from '@/components/nav-user';
import { NotificationBell } from '@/components/notification-bell';
import { OrganizationSelector } from '@/components/organization-selector';
import { ProjectSelector } from '@/components/project-selector';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="sticky top-0 z-30 flex h-[52px] shrink-0 items-center justify-between gap-2 border-b border-border/60 bg-background/90 px-4 backdrop-blur-xl transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-5">
            <div className="flex min-w-0 items-center gap-2.5">
                <SidebarTrigger className="-ml-1" />
                <div className="hidden h-4 w-px bg-border/80 sm:block" />
                <OrganizationSelector />
                <ProjectSelector />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex shrink-0 items-center gap-1">
                <NotificationBell />
                <NavUser layout="header" />
            </div>
        </header>
    );
}
