import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import ProjectRoleController from '@/actions/App/Http/Controllers/CommandCentre/ProjectRoleController';
import { Head, Link } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { ProjectRoleFormDialog } from '@/components/command-centre/project-role-form-dialog';
import { PageShell } from '@/components/command-centre/page-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { canProject } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';
import type { PermissionGroup } from '@/types/permission';

type ProjectRoleListItem = {
    id: number;
    name: string;
    slug: string;
    is_default: boolean;
    is_system: boolean;
    permissions_count: number;
    members_count: number;
};

type ProjectRolesIndexProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    project: { id: number; name: string };
    roles: ProjectRoleListItem[];
    permissionGroups: PermissionGroup[];
    permissions: CommandCentrePermissions;
};

export default function ProjectRolesIndex({
    organization,
    project,
    roles,
    permissionGroups,
    permissions,
}: ProjectRolesIndexProps) {
    const canShow = canProject(
        permissions,
        project.id,
        'project.roles.show',
    );
    const canStore = canProject(
        permissions,
        project.id,
        'project.roles.store',
    );

    return (
        <>
            <Head title={`Roles · ${project.name}`} />
            <PageShell
                title={`Project roles · ${project.name}`}
                subtitle={organization.name}
                stats={[{ label: 'Roles', value: roles.length }]}
                actions={
                    <div className="flex flex-wrap gap-2">
                        {canStore && (
                            <ProjectRoleFormDialog
                                organizationId={organization.id}
                                projectId={project.id}
                                permissionGroups={permissionGroups}
                            />
                        )}
                        <Button asChild variant="outline" size="sm">
                            <Link
                                href={ProjectController.show.url([
                                    organization.id,
                                    project.id,
                                ])}
                            >
                                Back to project
                            </Link>
                        </Button>
                    </div>
                }
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
                                ) : (
                                    <Badge variant="outline">Custom</Badge>
                                )
                            }
                            action={
                                canShow ? (
                                    <Button asChild variant="outline" size="sm">
                                        <Link
                                            href={ProjectRoleController.show.url(
                                                [
                                                    organization.id,
                                                    project.id,
                                                    role.id,
                                                ],
                                            )}
                                        >
                                            Manage
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
            </PageShell>
        </>
    );
}
