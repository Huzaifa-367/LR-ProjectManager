import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import NotificationPreferenceController from '@/actions/App/Http/Controllers/NotificationPreferenceController';
import { Link, router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { canOrg } from '@/hooks/use-org-permissions';
import { cn } from '@/lib/utils';

export function NotificationBell() {
    const { organizationContext } = usePage().props;
    const organization = organizationContext.selectedOrganization;
    const notifications = organizationContext.notifications;
    const permissions = organizationContext.permissions;

    if (
        organization === null ||
        notifications === null ||
        permissions === null ||
        !canOrg(permissions.org, 'org.notifications.index')
    ) {
        return null;
    }

    const unreadIds = notifications.recent
        .filter((notification) => notification.read_at === null)
        .map((notification) => notification.id);

    const markRecentRead = (): void => {
        if (unreadIds.length === 0) {
            return;
        }

        router.patch(
            NotificationController.markRead.url(organization.id),
            { notification_ids: unreadIds },
            { preserveScroll: true },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative"
                    data-test="notification-bell-button"
                >
                    <Bell className="size-4" />
                    {notifications.unreadCount > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] font-medium text-destructive-foreground">
                            {notifications.unreadCount > 9
                                ? '9+'
                                : notifications.unreadCount}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <DropdownMenuLabel className="flex items-center justify-between">
                    <span>Notifications</span>
                    {unreadIds.length > 0 && (
                        <button
                            type="button"
                            className="text-xs font-normal text-muted-foreground underline underline-offset-4"
                            onClick={markRecentRead}
                        >
                            Mark recent read
                        </button>
                    )}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {notifications.recent.length === 0 ? (
                    <div className="px-2 py-4 text-sm text-muted-foreground">
                        No notifications yet.
                    </div>
                ) : (
                    notifications.recent.map((notification) => (
                        <DropdownMenuItem key={notification.id} asChild>
                            <Link
                                href={
                                    notification.action_url ??
                                    NotificationController.index.url(
                                        organization.id,
                                    )
                                }
                                className={cn(
                                    'flex cursor-pointer flex-col items-start gap-0.5',
                                    notification.read_at === null &&
                                        'bg-accent/40',
                                )}
                                preserveScroll
                            >
                                <span className="font-medium">
                                    {notification.title}
                                </span>
                                <span className="line-clamp-2 text-xs text-muted-foreground">
                                    {notification.body}
                                </span>
                            </Link>
                        </DropdownMenuItem>
                    ))
                )}
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link
                        href={NotificationController.index.url(organization.id)}
                        className="w-full justify-center text-center text-sm"
                    >
                        View all
                    </Link>
                </DropdownMenuItem>
                {canOrg(
                    permissions.org,
                    'org.notification-preferences.show',
                ) && (
                    <DropdownMenuItem asChild>
                        <Link
                            href={NotificationPreferenceController.show.url(
                                organization.id,
                            )}
                            className="w-full justify-center text-center text-sm"
                        >
                            Preferences
                        </Link>
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
