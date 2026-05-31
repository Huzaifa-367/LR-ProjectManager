import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import ProjectMemberController from '@/actions/App/Http/Controllers/CommandCentre/ProjectMemberController';
import ProjectRoleController from '@/actions/App/Http/Controllers/CommandCentre/ProjectRoleController';
import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { canOrg, canProject } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type ProjectDetail = {
    id: number;
    name: string;
    objective: string | null;
    progress_percent: number;
    next_action: string | null;
    health: string;
};

type ProjectShowProps = {
    organization: OrganizationSummary;
    project: ProjectDetail;
    permissions: CommandCentrePermissions;
};

export default function ProjectShow({
    organization,
    project,
    permissions,
}: ProjectShowProps) {
    const canManageTeam = canProject(
        permissions,
        project.id,
        'project.members.index',
    );
    const canManageRoles = canProject(
        permissions,
        project.id,
        'project.roles.index',
    );

    return (
        <>
            <Head title={project.name} />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={project.name}
                        description={project.objective ?? 'Project overview'}
                    />
                    <Button asChild variant="outline">
                        <Link
                            href={ProjectController.index.url(organization.id)}
                        >
                            All projects
                        </Link>
                    </Button>
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Summary</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <div className="flex flex-wrap gap-2">
                            <Badge>{project.health}</Badge>
                            <Badge variant="outline">
                                {project.progress_percent}% complete
                            </Badge>
                        </div>
                        {project.next_action && (
                            <p>
                                <span className="font-medium">Next:</span>{' '}
                                {project.next_action}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <div className="flex flex-wrap gap-3">
                    {canManageTeam && (
                        <Button asChild variant="secondary">
                            <Link
                                href={ProjectMemberController.index.url([
                                    organization.id,
                                    project.id,
                                ])}
                            >
                                Team settings
                            </Link>
                        </Button>
                    )}
                    {canManageRoles && (
                        <Button asChild variant="secondary">
                            <Link
                                href={ProjectRoleController.index.url([
                                    organization.id,
                                    project.id,
                                ])}
                            >
                                Role permissions
                            </Link>
                        </Button>
                    )}
                    {canOrg(permissions.org, 'org.tasks.index') && (
                        <Button asChild variant="outline">
                            <Link
                                href={`/organizations/${organization.id}/tasks?project_id=${project.id}`}
                            >
                                View tasks
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}
