import OrganizationRoleController from '@/actions/App/Http/Controllers/OrganizationRoleController';
import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { CommandCard } from '@/components/command-centre/command-card';
import { OrganizationRoleDetailsForm } from '@/components/command-centre/organization-role-details-form';
import { SettingsShell } from '@/components/command-centre/settings-shell';
import PermissionMatrix from '@/components/permission-matrix';
import { SubmitButton } from '@/components/submit-button';
import { Badge } from '@/components/ui/badge';
import { canOrg } from '@/hooks/use-org-permissions';
import { buildOrganizationSettingsNav } from '@/lib/organization-settings-nav';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';
import type { PermissionGroup } from '@/types/permission';

type OrganizationRoleShowProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    role: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        is_system: boolean;
        members_count: number;
        permissions: string[];
    };
    permissionGroups: PermissionGroup[];
    lockedPermissions: string[];
    permissions: CommandCentrePermissions;
};

export default function OrganizationRoleShow({
    organization,
    role,
    permissionGroups,
    lockedPermissions,
    permissions,
}: OrganizationRoleShowProps) {
    const canSync = canOrg(permissions.org, 'org.roles.permissions.sync');
    const canUpdate = canOrg(permissions.org, 'org.roles.update');
    const canDestroy = canOrg(permissions.org, 'org.roles.destroy');
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>(
        role.permissions,
    );

    const groups = permissionGroups.map((group) => ({
        ...group,
        description: group.description ?? '',
    }));

    return (
        <>
            <Head title={`${role.name} · ${organization.name}`} />
            <SettingsShell
                title={role.name}
                description={`Permission matrix for the ${role.slug} role.`}
                backHref={OrganizationRoleController.index.url(
                    organization.id,
                )}
                backLabel="All roles"
                nav={buildOrganizationSettingsNav(
                    organization.id,
                    permissions,
                    OrganizationRoleController.show.url([
                        organization.id,
                        role.id,
                    ]),
                )}
            >
                <div className="flex flex-wrap gap-2">
                    {role.is_system && (
                        <Badge variant="secondary">System role</Badge>
                    )}
                    {!role.is_system && (
                        <Badge variant="outline">Custom role</Badge>
                    )}
                    {lockedPermissions.map((permission) => (
                        <Badge key={permission} variant="outline">
                            Locked: {permission}
                        </Badge>
                    ))}
                </div>

                <CommandCard title="Role details" dot="gold">
                    <OrganizationRoleDetailsForm
                        organizationId={organization.id}
                        role={role}
                        canUpdate={canUpdate}
                        canDestroy={canDestroy}
                    />
                </CommandCard>

                <CommandCard title="Permissions" dot="crimson">
                    {canSync ? (
                        <Form
                            {...OrganizationRoleController.syncPermissions.form([
                                organization.id,
                                role.id,
                            ])}
                            options={{ preserveScroll: true }}
                            className="space-y-4"
                        >
                            {({ processing }) => (
                                <>
                                    <PermissionMatrix
                                        groups={groups}
                                        value={selectedPermissions}
                                        onChange={setSelectedPermissions}
                                        disabled={!canSync}
                                    />
                                    <SubmitButton processing={processing}>
                                        Save permissions
                                    </SubmitButton>
                                </>
                            )}
                        </Form>
                    ) : (
                        <PermissionMatrix
                            groups={groups}
                            value={role.permissions}
                            onChange={() => undefined}
                            disabled
                        />
                    )}
                </CommandCard>
            </SettingsShell>
        </>
    );
}
