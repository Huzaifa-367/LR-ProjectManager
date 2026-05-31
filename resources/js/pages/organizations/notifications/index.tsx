import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import NotificationPreferenceController from '@/actions/App/Http/Controllers/NotificationPreferenceController';
import { Head, Link, router } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { EmptyState } from '@/components/command-centre/empty-state';
import { PageShell } from '@/components/command-centre/page-shell';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { CommandCentrePermissions, OrganizationSummary } from '@/types/organization';

type NotificationRow = {
    id: string;
    title: string;
    body: string;
    action_url: string | null;
    read_at: string | null;
    created_at: string;
};

type NotificationsIndexProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    notifications: NotificationRow[];
    permissions: CommandCentrePermissions;
};

export default function NotificationsIndex({
    organization,
    notifications,
}: NotificationsIndexProps) {
    const unreadCount = notifications.filter(
        (notification) => notification.read_at === null,
    ).length;
    const unreadIds = notifications
        .filter((notification) => notification.read_at === null)
        .map((notification) => notification.id);

    const markAllRead = (): void => {
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
        <>
            <Head title={`Notifications · ${organization.name}`} />
            <PageShell
                title="Notifications"
                subtitle={organization.name}
                stats={[
                    { label: 'Unread', value: unreadCount, tone: 'accent' },
                ]}
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link
                                href={NotificationPreferenceController.show.url(
                                    organization.id,
                                )}
                            >
                                Preferences
                            </Link>
                        </Button>
                        {unreadIds.length > 0 && (
                            <Button
                                type="button"
                                size="sm"
                                onClick={markAllRead}
                            >
                                Mark all read
                            </Button>
                        )}
                    </div>
                }
            >
                <CommandCard
                    title="In-app notifications"
                    description="Task assignments, reminders, and workspace updates."
                    dot="blue"
                >
                    {notifications.length === 0 ? (
                        <EmptyState>No notifications yet.</EmptyState>
                    ) : (
                        <ul className="divide-y divide-border/50">
                            {notifications.map((notification) => (
                                <li
                                    key={notification.id}
                                    className={cn(
                                        'py-3',
                                        notification.read_at === null &&
                                            'rounded-lg bg-primary/5 px-3 -mx-1',
                                    )}
                                >
                                    <div className="font-medium">
                                        {notification.action_url ? (
                                            <Link
                                                href={notification.action_url}
                                                className="hover:text-primary hover:underline"
                                            >
                                                {notification.title}
                                            </Link>
                                        ) : (
                                            notification.title
                                        )}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {notification.body}
                                    </p>
                                    <time className="text-xs text-muted-foreground">
                                        {new Date(
                                            notification.created_at,
                                        ).toLocaleString()}
                                    </time>
                                </li>
                            ))}
                        </ul>
                    )}
                </CommandCard>
            </PageShell>
        </>
    );
}
