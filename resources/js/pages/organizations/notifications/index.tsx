import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import NotificationPreferenceController from '@/actions/App/Http/Controllers/NotificationPreferenceController';
import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Notifications"
                        description={`Updates for ${organization.name}.`}
                    />
                    <div className="flex flex-wrap gap-3 text-sm">
                        <Link
                            href={NotificationPreferenceController.show.url(
                                organization.id,
                            )}
                            className="underline underline-offset-4"
                        >
                            Preferences
                        </Link>
                        {unreadIds.length > 0 && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={markAllRead}
                            >
                                Mark all read
                            </Button>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>In-app notifications</CardTitle>
                        <CardDescription>
                            Task assignments, reminders, and workspace updates.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="divide-y">
                        {notifications.length === 0 ? (
                            <p className="py-6 text-sm text-muted-foreground">
                                No notifications yet.
                            </p>
                        ) : (
                            notifications.map((notification) => (
                                <div
                                    key={notification.id}
                                    className={cn(
                                        'flex flex-col gap-1 py-4 first:pt-0 last:pb-0',
                                        notification.read_at === null &&
                                            'rounded-md bg-accent/30 px-3 -mx-3',
                                    )}
                                >
                                    <div className="font-medium">
                                        {notification.action_url ? (
                                            <Link
                                                href={notification.action_url}
                                                className="hover:underline"
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
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
