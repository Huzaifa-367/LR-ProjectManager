import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationReportsController from '@/actions/App/Http/Controllers/OrganizationReportsController';
import { Head } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { EmptyState } from '@/components/command-centre/empty-state';
import { SettingsShell } from '@/components/command-centre/settings-shell';
import { buildOrganizationSettingsNav } from '@/lib/organization-settings-nav';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

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
    permissions: CommandCentrePermissions;
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
    permissions,
}: OrganizationReportsProps) {
    const activeHref = OrganizationReportsController.index.url(
        organization.id,
    );

    return (
        <>
            <Head title={`Reports · ${organization.name}`} />
            <SettingsShell
                title="Operations reports"
                description={`Export and mail delivery summaries for ${organization.name}.`}
                backHref={OrganizationController.show.url(organization.id)}
                nav={buildOrganizationSettingsNav(
                    organization.id,
                    permissions,
                    activeHref,
                )}
            >
                <CommandCard
                    title="Exports"
                    description="Async CSV export jobs for this organization."
                    dot="crimson"
                >
                    <dl className="mb-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                        <div>
                            <dt className="text-muted-foreground">Pending</dt>
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
                            <dt className="text-muted-foreground">Failed</dt>
                            <dd className="text-lg font-semibold">
                                {exportSummary.failed}
                            </dd>
                        </div>
                    </dl>

                    {recentExports.length === 0 ? (
                        <EmptyState>No export jobs yet.</EmptyState>
                    ) : (
                        <ul
                            className="divide-y divide-border/50"
                            aria-label="Recent export jobs"
                        >
                            {recentExports.map((job) => (
                                <li key={job.id} className="tcm-list-row">
                                    <div>
                                        <p className="text-sm font-medium">
                                            {job.export_type} · {job.status}
                                        </p>
                                        {job.requested_by && (
                                            <p className="text-xs text-muted-foreground">
                                                Requested by {job.requested_by}
                                            </p>
                                        )}
                                        {job.error_message && (
                                            <p className="text-xs text-destructive">
                                                {job.error_message}
                                            </p>
                                        )}
                                    </div>
                                    <time className="text-[11px] text-muted-foreground">
                                        {formatDate(job.created_at)}
                                    </time>
                                </li>
                            ))}
                        </ul>
                    )}
                </CommandCard>

                <CommandCard
                    title="Mail delivery"
                    description="Outbound email activity in the last 7 days."
                    dot="gold"
                >
                    <dl className="mb-4 grid grid-cols-2 gap-3 text-sm">
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
                        <EmptyState>No failed deliveries recorded.</EmptyState>
                    ) : (
                        <ul
                            className="divide-y divide-border/50"
                            aria-label="Recent failed mail deliveries"
                        >
                            {recentFailedDeliveries.map((delivery) => (
                                <li key={delivery.id} className="tcm-list-row">
                                    <div>
                                        <p className="text-sm font-medium">
                                            {delivery.event_type ?? 'mail'}
                                            {delivery.recipient_email
                                                ? ` · ${delivery.recipient_email}`
                                                : ''}
                                        </p>
                                        {delivery.subject && (
                                            <p className="text-xs text-muted-foreground">
                                                {delivery.subject}
                                            </p>
                                        )}
                                        {delivery.error_message && (
                                            <p className="text-xs text-destructive">
                                                {delivery.error_message}
                                            </p>
                                        )}
                                    </div>
                                    <time className="text-[11px] text-muted-foreground">
                                        {formatDate(delivery.failed_at)}
                                    </time>
                                </li>
                            ))}
                        </ul>
                    )}
                </CommandCard>
            </SettingsShell>
        </>
    );
}
