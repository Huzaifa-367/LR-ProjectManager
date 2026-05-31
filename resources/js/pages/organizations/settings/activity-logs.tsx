import ActivityLogController from '@/actions/App/Http/Controllers/ActivityLogController';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import { Head } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { EmptyState } from '@/components/command-centre/empty-state';
import { SettingsShell } from '@/components/command-centre/settings-shell';
import { buildOrganizationSettingsNav } from '@/lib/organization-settings-nav';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

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
    permissions: CommandCentrePermissions;
};

export default function OrganizationActivityLogs({
    organization,
    logs,
    permissions,
}: ActivityLogsProps) {
    const activeHref = ActivityLogController.index.url(organization.id);

    return (
        <>
            <Head title={`Activity · ${organization.name}`} />
            <SettingsShell
                title="Activity log"
                description={`Recent audit events for ${organization.name}.`}
                backHref={OrganizationController.show.url(organization.id)}
                nav={buildOrganizationSettingsNav(
                    organization.id,
                    permissions,
                    activeHref,
                )}
            >
                <CommandCard title="Recent events" dot="gold">
                    {logs.length === 0 ? (
                        <EmptyState>No activity recorded yet.</EmptyState>
                    ) : (
                        <ul className="divide-y divide-border/50">
                            {logs.map((log) => (
                                <li key={log.id} className="tcm-list-row">
                                    <div>
                                        <p className="text-sm font-medium">
                                            {log.event}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {log.subject_type} #{log.subject_id}
                                            {log.actor_name
                                                ? ` · ${log.actor_name}`
                                                : ''}
                                        </p>
                                    </div>
                                    {log.created_at && (
                                        <time className="text-[11px] text-muted-foreground">
                                            {new Date(
                                                log.created_at,
                                            ).toLocaleString()}
                                        </time>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </CommandCard>
            </SettingsShell>
        </>
    );
}
