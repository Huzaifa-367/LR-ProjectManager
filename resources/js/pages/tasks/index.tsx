import ExportController from '@/actions/App/Http/Controllers/ExportController';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Head, Link, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { CommandCard } from '@/components/command-centre/command-card';
import { CreateTaskDialog } from '@/components/command-centre/create-task-dialog';
import { EmptyState } from '@/components/command-centre/empty-state';
import { PageShell } from '@/components/command-centre/page-shell';
import { StatusPill } from '@/components/command-centre/status-pill';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
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
            <PageShell
                title="Tasks"
                subtitle={organization.name}
                stats={[{ label: 'Visible', value: tasks.length }]}
                actions={
                    <div className="flex flex-wrap gap-2">
                        {canCreate && projects.length > 0 && (
                            <CreateTaskDialog
                                organizationId={organization.id}
                                projects={projects}
                            />
                        )}
                        {canExport && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={requestExport}
                            >
                                <Download className="size-4" />
                                Export CSV
                            </Button>
                        )}
                    </div>
                }
            >
                <CommandCard
                    title="All tasks"
                    description="Use the header project selector to filter by project."
                    dot="crimson"
                >
                    {tasks.length === 0 ? (
                        <EmptyState>
                            No tasks match your visibility scope.
                        </EmptyState>
                    ) : (
                        <ul className="divide-y divide-border/50">
                            {tasks.map((task) => (
                                <li key={task.id} className="tcm-list-row">
                                    <div className="min-w-0 flex-1">
                                        <Link
                                            href={TaskController.show.url([
                                                organization.id,
                                                task.id,
                                            ])}
                                            className={`block truncate text-sm font-medium hover:text-primary hover:underline ${task.is_done ? 'text-muted-foreground line-through' : ''}`}
                                        >
                                            {task.title}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {task.project_name ?? 'Project'} ·{' '}
                                            {task.kind}
                                        </p>
                                    </div>
                                    <StatusPill status={task.status} />
                                    <Button asChild variant="ghost" size="sm">
                                        <Link
                                            href={TaskController.show.url([
                                                organization.id,
                                                task.id,
                                            ])}
                                        >
                                            Open
                                        </Link>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </CommandCard>
            </PageShell>
        </>
    );
}
