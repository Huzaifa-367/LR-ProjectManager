import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationRoleController from '@/actions/App/Http/Controllers/OrganizationRoleController';
import { Head, Link } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { SettingsShell } from '@/components/command-centre/settings-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
import { buildOrganizationSettingsNav } from '@/lib/organization-settings-nav';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type OrgRoleListItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_system: boolean;
    permissions_count: number;
    members_count: number;
};

type OrganizationRolesIndexProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    roles: OrgRoleListItem[];
    permissions: CommandCentrePermissions;
};

export default function OrganizationRolesIndex({
    organization,
    roles,
    permissions,
}: OrganizationRolesIndexProps) {
    const canShow = canOrg(permissions.org, 'org.roles.show');
    const activeHref = OrganizationRoleController.index.url(organization.id);

    return (
        <>
            <Head title={`Roles · ${organization.name}`} />
            <SettingsShell
                title="Organization roles"
                description="Default roles created at organization bootstrap."
                backHref={OrganizationController.show.url(organization.id)}
                nav={buildOrganizationSettingsNav(
                    organization.id,
                    permissions,
                    activeHref,
                )}
            >
                <div className="grid gap-4 md:grid-cols-2">
                    {roles.map((role) => (
                        <CommandCard
                            key={role.id}
                            title={role.name}
                            description={`${role.permissions_count} permissions · ${role.members_count} members`}
                            dot={role.is_system ? 'gold' : 'blue'}
                            badge={
                                role.is_system ? (
                                    <Badge variant="secondary">System</Badge>
                                ) : undefined
                            }
                            action={
                                canShow ? (
                                    <Button asChild variant="outline" size="sm">
                                        <Link
                                            href={OrganizationRoleController.show.url(
                                                [
                                                    organization.id,
                                                    role.id,
                                                ],
                                            )}
                                        >
                                            Edit
                                        </Link>
                                    </Button>
                                ) : undefined
                            }
                        >
                            <p className="text-sm text-muted-foreground">
                                Slug:{' '}
                                <code className="rounded bg-muted px-1.5 py-0.5 text-xs">
                                    {role.slug}
                                </code>
                            </p>
                        </CommandCard>
                    ))}
                </div>
            </SettingsShell>
        </>
    );
}
