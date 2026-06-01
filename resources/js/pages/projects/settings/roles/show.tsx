import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import ProjectRoleController from '@/actions/App/Http/Controllers/CommandCentre/ProjectRoleController';
import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { CommandCard } from '@/components/command-centre/command-card';
import { PageShell } from '@/components/command-centre/page-shell';
import { ProjectRoleDetailsForm } from '@/components/command-centre/project-role-details-form';
import PermissionMatrix from '@/components/permission-matrix';
import { SubmitButton } from '@/components/submit-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { canProject } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';
import type { PermissionGroup } from '@/types/permission';

type ProjectRoleShowProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    project: { id: number; name: string };
    role: {
        id: number;
        name: string;
        slug: string;
        is_default: boolean;
        is_system: boolean;
        members_count: number;
        permissions: string[];
    };
    permissionGroups: PermissionGroup[];
    permissions: CommandCentrePermissions;
};

export default function ProjectRoleShow({
    organization,
    project,
    role,
    permissionGroups,
    permissions,
}: ProjectRoleShowProps) {
    const canSync = canProject(
        permissions,
        project.id,
        'project.roles.permissions.sync',
    );
    const canUpdate = canProject(
        permissions,
        project.id,
        'project.roles.update',
    );
    const canDestroy = canProject(
        permissions,
        project.id,
        'project.roles.destroy',
    );
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>(
        role.permissions,
    );

    const groups = permissionGroups.map((group) => ({
        ...group,
        description: group.description ?? '',
    }));

    return (
        <>
            <Head title={`${role.name} · ${project.name}`} />
            <PageShell
                title={role.name}
                subtitle={`Project role · ${project.name}`}
                breadcrumbs={[
                    {
                        title: organization.name,
                        href: `/organizations/${organization.id}/command-centre`,
                    },
                    {
                        title: project.name,
                        href: ProjectController.show.url([
                            organization.id,
                            project.id,
                        ]),
                    },
                    {
                        title: 'Roles',
                        href: ProjectRoleController.index.url([
                            organization.id,
                            project.id,
                        ]),
                    },
                    { title: role.name },
                ]}
                actions={
                    <Button asChild variant="outline" size="sm">
                        <Link
                            href={ProjectRoleController.index.url([
                                organization.id,
                                project.id,
                            ])}
                        >
                            All roles
                        </Link>
                    </Button>
                }
            >
                <div className="flex flex-wrap gap-2">
                    {role.is_system && (
                        <Badge variant="secondary">System role</Badge>
                    )}
                    {!role.is_system && (
                        <Badge variant="outline">Custom role</Badge>
                    )}
                </div>

                <CommandCard title="Role details" dot="gold">
                    <ProjectRoleDetailsForm
                        organizationId={organization.id}
                        projectId={project.id}
                        role={role}
                        canUpdate={canUpdate}
                        canDestroy={canDestroy}
                    />
                </CommandCard>

                <CommandCard title="Permissions" dot="crimson">
                    {canSync ? (
                        <Form
                            {...ProjectRoleController.syncPermissions.form([
                                organization.id,
                                project.id,
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
            </PageShell>
        </>
    );
}
