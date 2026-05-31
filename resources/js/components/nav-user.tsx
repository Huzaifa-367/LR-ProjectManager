import { usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

type NavUserProps = {
    layout?: 'sidebar' | 'header';
};

export function NavUser({ layout = 'sidebar' }: NavUserProps) {
    const { auth } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    if (!auth.user) {
        return null;
    }

    const menuContent = (
        <DropdownMenuContent
            className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
            align="end"
            side={
                layout === 'header' || isMobile
                    ? 'bottom'
                    : state === 'collapsed'
                      ? 'left'
                      : 'bottom'
            }
        >
            <UserMenuContent user={auth.user} />
        </DropdownMenuContent>
    );

    if (layout === 'header') {
        return (
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        className={cn(
                            'h-10 gap-2 px-2',
                            'data-[state=open]:bg-accent',
                        )}
                        data-test="header-user-menu-button"
                    >
                        <UserInfo user={auth.user} showName={false} />
                        <ChevronsUpDown className="size-4 text-muted-foreground" />
                    </Button>
                </DropdownMenuTrigger>
                {menuContent}
            </DropdownMenu>
        );
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent"
                            data-test="sidebar-menu-button"
                        >
                            <UserInfo user={auth.user} />
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    {menuContent}
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
