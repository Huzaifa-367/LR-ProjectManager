import ActivityLogController from '@/actions/App/Http/Controllers/ActivityLogController';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import { Head } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { EditOrganizationDialog } from '@/components/command-centre/edit-organization-dialog';
import { SettingsShell } from '@/components/command-centre/settings-shell';
import { canOrg } from '@/hooks/use-org-permissions';
import { buildOrganizationSettingsNav } from '@/lib/organization-settings-nav';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type OrganizationSettingsProps = {
    organization: OrganizationSummary & {
        settings: {
            timezone: string;
            focus_cap: number;
            ai_enabled: boolean;
        };
    };
    permissions: CommandCentrePermissions;
};

export default function OrganizationSettingsIndex({
    organization,
    permissions,
}: OrganizationSettingsProps) {
    const canUpdate = canOrg(permissions.org, 'org.organizations.update');
    const activeHref = OrganizationController.show.url(organization.id);

    return (
        <>
            <Head title={`Settings · ${organization.name}`} />
            <SettingsShell
                title="Organization settings"
                description={`Profile and defaults for ${organization.name}.`}
                nav={buildOrganizationSettingsNav(
                    organization.id,
                    permissions,
                    activeHref,
                )}
                actions={
                    canUpdate ? (
                        <EditOrganizationDialog organization={organization} />
                    ) : undefined
                }
            >
                <CommandCard title="Profile" dot="crimson">
                    <dl className="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted-foreground">Name</dt>
                            <dd className="font-medium">{organization.name}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Timezone</dt>
                            <dd>{organization.settings.timezone}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Daily focus cap
                            </dt>
                            <dd>{organization.settings.focus_cap}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                AI features
                            </dt>
                            <dd>
                                {organization.settings.ai_enabled
                                    ? 'Enabled'
                                    : 'Disabled'}
                            </dd>
                        </div>
                    </dl>
                </CommandCard>
            </SettingsShell>
        </>
    );
}
