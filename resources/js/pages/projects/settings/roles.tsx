import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import { Head, Link } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { PageShell } from '@/components/command-centre/page-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { OrganizationSummary } from '@/types/organization';

type ProjectRoleItem = {
    id: number;
    name: string;
    slug: string;
    is_default: boolean;
    permissions: string[];
};

type ProjectRolesProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    project: { id: number; name: string };
    roles: ProjectRoleItem[];
    permissionGroups: Record<
        string,
        { label: string; permissions: Record<string, string> }
    >;
};

export default function ProjectRolesSettings({
    organization,
    project,
    roles,
}: ProjectRolesProps) {
    return (
        <>
            <Head title={`Roles · ${project.name}`} />
            <PageShell
                title={`Project roles · ${project.name}`}
                subtitle={organization.name}
                stats={[{ label: 'Roles', value: roles.length }]}
                actions={
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
                }
            >
                <div className="grid gap-4">
                    {roles.map((role) => (
                        <CommandCard
                            key={role.id}
                            title={role.name}
                            dot={role.is_default ? 'gold' : 'blue'}
                            badge={
                                role.is_default ? (
                                    <Badge variant="secondary">Default</Badge>
                                ) : undefined
                            }
                        >
                            <p className="text-sm text-muted-foreground">
                                {role.permissions.length} permissions · slug{' '}
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
