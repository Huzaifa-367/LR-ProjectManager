import { Link } from '@inertiajs/react';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { EmptyState } from '@/components/command-centre/empty-state';
import { StatusPill } from '@/components/command-centre/status-pill';
import { TaskKindBadge } from '@/components/command-centre/task-kind-badge';
import { Button } from '@/components/ui/button';
import { formatDueDateLabel } from '@/lib/task-options';

export type TasksListItem = {
    id: number;
    kind: string;
    project_id: number;
    project_name: string | undefined;
    title: string;
    status: string;
    is_done: boolean;
    priority: string | null;
    deadline_date: string | null;
    assignees: Array<{ id: number; display_name: string | null }>;
};

type TasksListViewProps = {
    organizationId: number;
    tasks: TasksListItem[];
};

export function TasksListView({ organizationId, tasks }: TasksListViewProps) {
    if (tasks.length === 0) {
        return (
            <EmptyState>
                No tasks match your visibility scope.
            </EmptyState>
        );
    }

    return (
        <ul className="divide-y divide-border/50">
            {tasks.map((task) => (
                <li key={task.id} className="tcm-list-row">
                    <div className="min-w-0 flex-1">
                        <div className="mb-1 flex flex-wrap items-center gap-2">
                            <TaskKindBadge kind={task.kind} />
                            <Link
                                href={TaskController.show.url([
                                    organizationId,
                                    task.id,
                                ])}
                                className={`truncate text-sm font-medium hover:text-primary hover:underline ${task.is_done ? 'text-muted-foreground line-through' : ''}`}
                            >
                                {task.title}
                            </Link>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {task.project_name ?? 'Project'}
                            {task.deadline_date !== null && (
                                <>
                                    {' · '}
                                    Due{' '}
                                    {formatDueDateLabel(task.deadline_date)}
                                </>
                            )}
                        </p>
                    </div>
                    <StatusPill status={task.status} />
                    <Button asChild variant="ghost" size="sm">
                        <Link
                            href={TaskController.show.url([
                                organizationId,
                                task.id,
                            ])}
                        >
                            Open
                        </Link>
                    </Button>
                </li>
            ))}
        </ul>
    );
}
