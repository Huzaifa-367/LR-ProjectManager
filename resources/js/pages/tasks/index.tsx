import ExportController from '@/actions/App/Http/Controllers/ExportController';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Head, Link, router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
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
import { useOrganizationContext } from '@/hooks/use-organization-context';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type TaskListItem = {
    id: number;
    kind: string;
    project_id: number;
    project_name: string | undefined;
    title: string;
    status: string;
    is_done: boolean;
    assignees: Array<{ id: number; display_name: string | null }>;
};

type TasksIndexProps = {
    organization: OrganizationSummary;
    tasks: TaskListItem[];
    projects: Array<{ id: number; name: string }>;
    filters: { project_id: number | null; kind: string | null };
    permissions: CommandCentrePermissions;
};

export default function TasksIndex({
    organization,
    tasks,
    projects,
    filters,
    permissions,
}: TasksIndexProps) {
    const { selectedProject } = useOrganizationContext();
    const canCreate = canOrg(permissions.org, 'org.tasks.store');
    const canExport = canOrg(permissions.org, 'org.exports.store');

    const requestExport = (): void => {
        router.post(
            ExportController.store.url(organization.id),
            {
                export_type: 'tasks_csv',
                filters: {
                    project_id: filters.project_id,
                    kind: filters.kind,
                },
            },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Tasks" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={`Tasks · ${organization.name}`}
                        description="Unified tasks across visible projects."
                    />
                    {canExport && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={requestExport}
                        >
                            <Download className="size-4" />
                            Export CSV
                        </Button>
                    )}
                </div>

                {canCreate && projects.length > 0 && (
                    <Card className="max-w-xl">
                        <CardHeader>
                            <CardTitle>Create task</CardTitle>
                            <CardDescription>
                                Assignees must already be on the project team.
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
                                        TaskController.store.url(
                                            organization.id,
                                        ),
                                        {
                                            kind: 'task',
                                            project_id: formData.get(
                                                'project_id',
                                            ),
                                            title: formData.get('title'),
                                        },
                                    );
                                }}
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="project_id">Project</Label>
                                    <select
                                        id="project_id"
                                        name="project_id"
                                        required
                                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                        defaultValue={
                                            selectedProject?.id?.toString() ??
                                            ''
                                        }
                                    >
                                        <option value="" disabled>
                                            Select project
                                        </option>
                                        {projects.map((project) => (
                                            <option
                                                key={project.id}
                                                value={project.id}
                                            >
                                                {project.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="title">Title</Label>
                                    <Input
                                        id="title"
                                        name="title"
                                        required
                                        placeholder="Follow up with vendor"
                                    />
                                </div>
                                <Button type="submit">
                                    <Plus className="size-4" />
                                    Add task
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-3">
                    {tasks.length === 0 ? (
                        <Card>
                            <CardContent className="p-6 text-sm text-muted-foreground">
                                No tasks match your visibility scope.
                            </CardContent>
                        </Card>
                    ) : (
                        tasks.map((task) => (
                            <Card key={task.id}>
                                <CardContent className="flex items-center justify-between gap-4 p-4">
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {task.title}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {task.project_name ?? 'Project'} ·{' '}
                                            {task.kind}
                                        </p>
                                        <div className="mt-1 flex flex-wrap gap-2">
                                            <Badge variant="outline">
                                                {task.status}
                                            </Badge>
                                            {task.is_done && (
                                                <Badge>Done</Badge>
                                            )}
                                        </div>
                                    </div>
                                    <Button asChild variant="outline">
                                        <Link
                                            href={TaskController.show.url([
                                                organization.id,
                                                task.id,
                                            ])}
                                        >
                                            Open
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>

                {filters.kind && (
                    <Link
                        href={TaskController.index.url(organization.id)}
                        className="text-sm underline underline-offset-4"
                    >
                        Clear filters
                    </Link>
                )}
            </div>
        </>
    );
}
