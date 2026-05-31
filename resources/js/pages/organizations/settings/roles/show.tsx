import OrganizationRoleController from '@/actions/App/Http/Controllers/OrganizationRoleController';
import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { CommandCard } from '@/components/command-centre/command-card';
import { SettingsShell } from '@/components/command-centre/settings-shell';
import PermissionMatrix from '@/components/permission-matrix';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
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
            >
                <div className="flex flex-wrap gap-2">
                    {role.is_system && (
                        <Badge variant="secondary">System role</Badge>
                    )}
                    {lockedPermissions.map((permission) => (
                        <Badge key={permission} variant="outline">
                            Locked: {permission}
                        </Badge>
                    ))}
                </div>

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
                                    <Button type="submit" disabled={processing}>
                                        Save permissions
                                    </Button>
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
