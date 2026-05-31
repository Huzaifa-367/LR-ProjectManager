import OrganizationRoleController from '@/actions/App/Http/Controllers/OrganizationRoleController';
import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
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
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={role.name}
                        description={`Permission matrix for the ${role.slug} role.`}
                    />
                    <Link
                        href={OrganizationRoleController.index.url(
                            organization.id,
                        )}
                        className="text-sm underline underline-offset-4"
                    >
                        All roles
                    </Link>
                </div>

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
            </div>
        </>
    );
}
