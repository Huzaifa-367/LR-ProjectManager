import ActivityLogController from '@/actions/App/Http/Controllers/ActivityLogController';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationMemberController from '@/actions/App/Http/Controllers/OrganizationMemberController';
import OrganizationRoleController from '@/actions/App/Http/Controllers/OrganizationRoleController';
import { Head, Link } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { EditOrganizationDialog } from '@/components/command-centre/edit-organization-dialog';
import { SettingsShell } from '@/components/command-centre/settings-shell';
import { Button } from '@/components/ui/button';
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
    const canManageMembers = canOrg(permissions.org, 'org.members.index');
    const canManageRoles = canOrg(permissions.org, 'org.roles.index');
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
                {(canManageMembers || canManageRoles) && (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {canManageMembers && (
                            <CommandCard
                                title="Members"
                                description="Add people to the organization roster, assign org roles, and send invitations."
                                dot="blue"
                                action={
                                    <Button asChild variant="outline" size="sm">
                                        <Link
                                            href={OrganizationMemberController.index.url(
                                                organization.id,
                                            )}
                                        >
                                            Manage members
                                        </Link>
                                    </Button>
                                }
                            />
                        )}
                        {canManageRoles && (
                            <CommandCard
                                title="Roles"
                                description="Create custom organization roles and edit permission matrices."
                                dot="gold"
                                action={
                                    <Button asChild variant="outline" size="sm">
                                        <Link
                                            href={OrganizationRoleController.index.url(
                                                organization.id,
                                            )}
                                        >
                                            Manage roles
                                        </Link>
                                    </Button>
                                }
                            />
                        )}
                    </div>
                )}

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
