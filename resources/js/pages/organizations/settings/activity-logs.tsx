import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { OrganizationSummary } from '@/types/organization';

type ActivityLogRow = {
    id: number;
    event: string;
    subject_type: string;
    subject_id: number;
    actor_name: string | null;
    created_at: string | null;
};

type ActivityLogsProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    logs: ActivityLogRow[];
};

export default function OrganizationActivityLogs({
    organization,
    logs,
}: ActivityLogsProps) {
    return (
        <>
            <Head title={`Activity · ${organization.name}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="sr-only">Activity log</h1>
                        <Heading
                            title="Activity log"
                            description={`Recent audit events for ${organization.name}.`}
                        />
                    </div>
                    <Link
                        href={OrganizationController.show.url(organization.id)}
                        className="text-sm underline underline-offset-4"
                    >
                        Settings
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent events</CardTitle>
                        <CardDescription>
                            Append-only audit trail for tasks, projects, and
                            members.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {logs.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No activity recorded yet.
                            </p>
                        ) : (
                            logs.map((log) => (
                                <div
                                    key={log.id}
                                    className="rounded-md border px-3 py-2 text-sm"
                                >
                                    <div className="font-medium">{log.event}</div>
                                    <div className="text-muted-foreground">
                                        {log.subject_type} #{log.subject_id}
                                        {log.actor_name
                                            ? ` · ${log.actor_name}`
                                            : ''}
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
