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

type ExportSummary = {
    pending: number;
    processing: number;
    completed: number;
    failed: number;
};

type ExportRow = {
    id: number;
    export_type: string;
    status: string;
    requested_by: string | null;
    error_message: string | null;
    completed_at: string | null;
    created_at: string | null;
};

type DeliverySummary = {
    sent_last_7_days: number;
    failed_last_7_days: number;
};

type FailedDeliveryRow = {
    id: number;
    event_type: string | null;
    recipient_email: string | null;
    subject: string | null;
    error_message: string | null;
    failed_at: string | null;
};

type OrganizationReportsProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    exportSummary: ExportSummary;
    recentExports: ExportRow[];
    deliverySummary: DeliverySummary;
    recentFailedDeliveries: FailedDeliveryRow[];
};

function formatDate(value: string | null): string {
    if (value === null) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

export default function OrganizationReports({
    organization,
    exportSummary,
    recentExports,
    deliverySummary,
    recentFailedDeliveries,
}: OrganizationReportsProps) {
    return (
        <>
            <Head title={`Reports · ${organization.name}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="sr-only">Operations reports</h1>
                        <Heading
                            title="Operations reports"
                            description={`Export and mail delivery summaries for ${organization.name}.`}
                        />
                    </div>
                    <Link
                        href={OrganizationController.show.url(organization.id)}
                        className="text-sm underline underline-offset-4"
                    >
                        Settings
                    </Link>
                </div>

                <section aria-labelledby="export-summary-heading">
                    <Card>
                        <CardHeader>
                            <CardTitle id="export-summary-heading">
                                Exports
                            </CardTitle>
                            <CardDescription>
                                Async CSV export jobs for this organization.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <dl className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Pending
                                    </dt>
                                    <dd className="text-lg font-semibold">
                                        {exportSummary.pending}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Processing
                                    </dt>
                                    <dd className="text-lg font-semibold">
                                        {exportSummary.processing}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Completed
                                    </dt>
                                    <dd className="text-lg font-semibold">
                                        {exportSummary.completed}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Failed
                                    </dt>
                                    <dd className="text-lg font-semibold">
                                        {exportSummary.failed}
                                    </dd>
                                </div>
                            </dl>

                            {recentExports.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No export jobs yet.
                                </p>
                            ) : (
                                <ul
                                    className="divide-y rounded-md border"
                                    aria-label="Recent export jobs"
                                >
                                    {recentExports.map((job) => (
                                        <li
                                            key={job.id}
                                            className="px-3 py-2 text-sm"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <span className="font-medium">
                                                    {job.export_type} ·{' '}
                                                    {job.status}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {formatDate(job.created_at)}
                                                </span>
                                            </div>
                                            {job.requested_by && (
                                                <div className="text-muted-foreground">
                                                    Requested by{' '}
                                                    {job.requested_by}
                                                </div>
                                            )}
                                            {job.error_message && (
                                                <div className="text-destructive">
                                                    {job.error_message}
                                                </div>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section aria-labelledby="delivery-summary-heading">
                    <Card>
                        <CardHeader>
                            <CardTitle id="delivery-summary-heading">
                                Mail delivery
                            </CardTitle>
                            <CardDescription>
                                Outbound email activity in the last 7 days.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <dl className="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Sent (7 days)
                                    </dt>
                                    <dd className="text-lg font-semibold">
                                        {deliverySummary.sent_last_7_days}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Failed (7 days)
                                    </dt>
                                    <dd className="text-lg font-semibold">
                                        {deliverySummary.failed_last_7_days}
                                    </dd>
                                </div>
                            </dl>

                            {recentFailedDeliveries.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No failed deliveries recorded.
                                </p>
                            ) : (
                                <ul
                                    className="divide-y rounded-md border"
                                    aria-label="Recent failed mail deliveries"
                                >
                                    {recentFailedDeliveries.map((delivery) => (
                                        <li
                                            key={delivery.id}
                                            className="px-3 py-2 text-sm"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <span className="font-medium">
                                                    {delivery.event_type ??
                                                        'mail'}
                                                    {delivery.recipient_email
                                                        ? ` · ${delivery.recipient_email}`
                                                        : ''}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {formatDate(
                                                        delivery.failed_at,
                                                    )}
                                                </span>
                                            </div>
                                            {delivery.subject && (
                                                <div className="text-muted-foreground">
                                                    {delivery.subject}
                                                </div>
                                            )}
                                            {delivery.error_message && (
                                                <div className="text-destructive">
                                                    {delivery.error_message}
                                                </div>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </>
    );
}
