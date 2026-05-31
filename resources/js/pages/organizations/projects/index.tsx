import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import { Head, Link, router } from '@inertiajs/react';
import { FolderKanban, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { canOrg } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type ProjectListItem = {
    id: number;
    name: string;
    objective: string | null;
    progress_percent: number;
    next_action: string | null;
    health: string;
};

type ProjectsIndexProps = {
    organization: OrganizationSummary;
    projects: ProjectListItem[];
    permissions: CommandCentrePermissions;
};

const healthVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    active: 'default',
    progressing: 'secondary',
    steady: 'outline',
};

export default function ProjectsIndex({
    organization,
    projects,
    permissions,
}: ProjectsIndexProps) {
    const canCreate = canOrg(permissions.org, 'org.projects.store');

    return (
        <>
            <Head title="Projects" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={`Projects · ${organization.name}`}
                        description="Track initiatives across your organization."
                    />
                </div>

                {canCreate && (
                    <Card className="max-w-xl">
                        <CardHeader>
                            <CardTitle>Create project</CardTitle>
                            <CardDescription>
                                Bootstrap a project with default roles and add
                                yourself as project owner.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                className="flex flex-col gap-4"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    const form = event.currentTarget;
                                    const formData = new FormData(form);

                                    router.post(
                                        ProjectController.store.url(
                                            organization.id,
                                        ),
                                        {
                                            name: formData.get('name'),
                                            objective: formData.get(
                                                'objective',
                                            ),
                                        },
                                    );
                                }}
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        placeholder="Q3 launch"
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="objective">Objective</Label>
                                    <Input
                                        id="objective"
                                        name="objective"
                                        placeholder="What does success look like?"
                                    />
                                </div>
                                <Button type="submit">
                                    <Plus className="size-4" />
                                    Create project
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-3">
                    {projects.length === 0 ? (
                        <Card>
                            <CardContent className="p-6 text-sm text-muted-foreground">
                                No projects yet.
                                {canCreate
                                    ? ' Create one above to get started.'
                                    : ''}
                            </CardContent>
                        </Card>
                    ) : (
                        projects.map((project) => (
                            <Card key={project.id}>
                                <CardContent className="flex items-center justify-between gap-4 p-4">
                                    <div className="flex min-w-0 items-center gap-3">
                                        <div className="flex size-10 shrink-0 items-center justify-center rounded-md bg-muted">
                                            <FolderKanban className="size-5 text-muted-foreground" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {project.name}
                                            </p>
                                            {project.objective && (
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {project.objective}
                                                </p>
                                            )}
                                            <div className="mt-1 flex flex-wrap gap-2">
                                                <Badge
                                                    variant={
                                                        healthVariant[
                                                            project.health
                                                        ] ?? 'outline'
                                                    }
                                                >
                                                    {project.health}
                                                </Badge>
                                                <Badge variant="outline">
                                                    {project.progress_percent}%
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                    <Button asChild variant="outline">
                                        <Link
                                            href={ProjectController.show.url(
                                                [organization.id, project.id],
                                            )}
                                        >
                                            Open
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>
            </div>
        </>
    );
}
