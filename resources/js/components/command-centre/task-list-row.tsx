import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Link, router } from '@inertiajs/react';
import { AssigneeAvatars } from '@/components/command-centre/assignee-avatars';
import { PriorityIndicator } from '@/components/command-centre/priority-indicator';
import { StatusPill } from '@/components/command-centre/status-pill';
import { TaskKey } from '@/components/command-centre/task-key';
import { TaskKindBadge } from '@/components/command-centre/task-kind-badge';
import { TaskMetaLine } from '@/components/command-centre/task-deadline-label';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
import { formatDueDateLabel, type DueDateUi } from '@/lib/task-options';
import { cn } from '@/lib/utils';

export type TaskListRowTask = {
    id: number;
    kind: string;
    title: string;
    status: string;
    is_done: boolean;
    priority: string | null;
    project_name?: string | null;
    deadline_date?: string | null;
    deadline_ui?: DueDateUi | null;
    assignees?: Array<{ id: number; display_name: string | null }>;
};

type TaskListRowProps = {
    organizationId: number;
    task: TaskListRowTask;
    permissions?: string[];
    showCompleteAction?: boolean;
    className?: string;
};

export function TaskListRow({
    organizationId,
    task,
    permissions = [],
    showCompleteAction = false,
    className,
}: TaskListRowProps) {
    const assignees = task.assignees ?? [];
    const canToggle =
        showCompleteAction &&
        canOrg(permissions, 'org.tasks.toggle-done') &&
        !task.is_done;

    return (
        <div
            className={cn(
                'tcm-list-row group items-center gap-3',
                className,
            )}
        >
            <div className="min-w-0 flex-1">
                <div className="mb-1.5 flex flex-wrap items-center gap-2">
                    <TaskKindBadge kind={task.kind} />
                    <TaskKey taskId={task.id} />
                    <PriorityIndicator priority={task.priority} />
                </div>
                <Link
                    href={TaskController.show.url([organizationId, task.id])}
                    className={cn(
                        'block text-sm font-medium leading-snug hover:text-primary hover:underline',
                        task.is_done &&
                            'text-muted-foreground line-through',
                    )}
                >
                    {task.title}
                </Link>
                {(task.project_name !== undefined ||
                    task.deadline_date !== undefined ||
                    task.deadline_ui !== undefined) && (
                    <div className="mt-1">
                        {task.deadline_ui !== undefined ? (
                            <TaskMetaLine
                                projectName={task.project_name ?? null}
                                deadlineDate={task.deadline_date ?? null}
                                deadlineUi={task.deadline_ui ?? null}
                            />
                        ) : (
                            <p className="text-xs text-muted-foreground">
                                {task.project_name ?? 'Project'}
                                {task.deadline_date !== null &&
                                    task.deadline_date !== undefined && (
                                        <>
                                            {' · '}
                                            Due{' '}
                                            {formatDueDateLabel(
                                                task.deadline_date,
                                            )}
                                        </>
                                    )}
                            </p>
                        )}
                    </div>
                )}
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <AssigneeAvatars assignees={assignees} />
                <StatusPill status={task.status} />
                {canToggle && (
                    <Button
                        size="sm"
                        variant="outline"
                        className="h-8 opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
                        onClick={() => {
                            router.patch(
                                TaskController.toggleDone.url([
                                    organizationId,
                                    task.id,
                                ]),
                            );
                        }}
                    >
                        Done
                    </Button>
                )}
                <Button asChild variant="ghost" size="sm" className="h-8">
                    <Link
                        href={TaskController.show.url([
                            organizationId,
                            task.id,
                        ])}
                    >
                        Open
                    </Link>
                </Button>
            </div>
        </div>
    );
}
