import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Link, router } from '@inertiajs/react';
import { GripVertical } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { EmptyState } from '@/components/command-centre/empty-state';
import { StatusPill } from '@/components/command-centre/status-pill';
import { useIsMobile } from '@/hooks/use-mobile';
import { canOrg } from '@/hooks/use-org-permissions';
import type { CommandCentrePermissions } from '@/types/organization';
import { cn } from '@/lib/utils';

type KanbanTask = {
    id: number;
    title: string;
    status: string;
    is_done: boolean;
    priority: string | null;
    assignees: Array<{ id: number; display_name: string | null }>;
};

type KanbanColumn = {
    status: TaskStatusValue;
    label: string;
    dot: 'crimson' | 'gold' | 'blue' | 'green';
};

type TaskStatusValue =
    | 'pending'
    | 'in_progress'
    | 'done'
    | 'stuck'
    | 'hold'
    | 'follow_up';

/** One column per `App\Enums\TaskStatus` case. */
const KANBAN_COLUMNS: KanbanColumn[] = [
    { status: 'pending', label: 'Pending', dot: 'gold' },
    { status: 'in_progress', label: 'In progress', dot: 'blue' },
    { status: 'done', label: 'Done', dot: 'green' },
    { status: 'stuck', label: 'Stuck', dot: 'crimson' },
    { status: 'hold', label: 'On hold', dot: 'gold' },
    { status: 'follow_up', label: 'Follow up', dot: 'blue' },
];

const STATUS_OPTIONS = KANBAN_COLUMNS.map((column) => ({
    value: column.status,
    label: column.label,
}));

const dotClass: Record<KanbanColumn['dot'], string> = {
    crimson: 'bg-primary shadow-[0_0_8px_color-mix(in_oklab,var(--primary)_40%,transparent)]',
    gold: 'bg-amber-500 shadow-[0_0_8px_rgba(212,168,67,0.35)]',
    blue: 'bg-blue-500 shadow-[0_0_8px_rgba(91,156,246,0.35)]',
    green: 'bg-emerald-500 shadow-[0_0_8px_rgba(46,204,113,0.35)]',
};

type ProjectKanbanProps = {
    organizationId: number;
    tasks: KanbanTask[];
    permissions: CommandCentrePermissions;
};

export function ProjectKanban({
    organizationId,
    tasks,
    permissions,
}: ProjectKanbanProps) {
    const isMobile = useIsMobile();
    const canUpdateStatus = canOrg(permissions.org, 'org.tasks.status.update');
    const canDrag = canUpdateStatus && !isMobile;

    const [boardTasks, setBoardTasks] = useState<KanbanTask[]>(tasks);
    const [draggingTaskId, setDraggingTaskId] = useState<number | null>(null);
    const [dropTargetStatus, setDropTargetStatus] =
        useState<TaskStatusValue | null>(null);

    useEffect(() => {
        setBoardTasks(tasks);
    }, [tasks]);

    const tasksByColumn = useMemo(
        () =>
            KANBAN_COLUMNS.map((column) => ({
                column,
                tasks: boardTasks.filter((task) => task.status === column.status),
            })),
        [boardTasks],
    );

    const updateStatus = (taskId: number, status: TaskStatusValue): void => {
        setBoardTasks((current) =>
            current.map((task) =>
                task.id === taskId ? { ...task, status } : task,
            ),
        );

        router.patch(
            TaskController.updateStatus.url([organizationId, taskId]),
            { status },
            { preserveScroll: true },
        );
    };

    const handleDragStart = (
        event: React.DragEvent<HTMLDivElement>,
        taskId: number,
    ): void => {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(taskId));
        setDraggingTaskId(taskId);
    };

    const handleDragEnd = (): void => {
        setDraggingTaskId(null);
        setDropTargetStatus(null);
    };

    const handleColumnDragOver = (
        event: React.DragEvent<HTMLElement>,
        status: TaskStatusValue,
    ): void => {
        if (!canDrag) {
            return;
        }

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        setDropTargetStatus(status);
    };

    const handleColumnDrop = (
        event: React.DragEvent<HTMLElement>,
        status: TaskStatusValue,
    ): void => {
        event.preventDefault();
        setDropTargetStatus(null);
        setDraggingTaskId(null);

        if (!canDrag) {
            return;
        }

        const rawTaskId = event.dataTransfer.getData('text/plain');
        const taskId = Number.parseInt(rawTaskId, 10);

        if (Number.isNaN(taskId)) {
            return;
        }

        const task = boardTasks.find((row) => row.id === taskId);

        if (task === undefined || task.status === status) {
            return;
        }

        updateStatus(taskId, status);
    };

    if (tasks.length === 0) {
        return (
            <EmptyState>
                No tasks on this project yet. Create one to populate the board.
            </EmptyState>
        );
    }

    return (
        <div className="tcm-kanban-board">
            {tasksByColumn.map(({ column, tasks: columnTasks }) => (
                <section
                    key={column.status}
                    className={cn(
                        'tcm-kanban-column',
                        dropTargetStatus === column.status &&
                            'tcm-kanban-column--drop-target',
                    )}
                    aria-label={`${column.label} column`}
                    onDragOver={(event) =>
                        handleColumnDragOver(event, column.status)
                    }
                    onDragLeave={(event) => {
                        if (
                            event.currentTarget.contains(
                                event.relatedTarget as Node,
                            )
                        ) {
                            return;
                        }

                        setDropTargetStatus(null);
                    }}
                    onDrop={(event) => handleColumnDrop(event, column.status)}
                >
                    <header className="flex items-center justify-between gap-2 border-b border-border/60 px-3 py-2.5">
                        <div className="flex items-center gap-2">
                            <span
                                className={cn(
                                    'size-1.5 shrink-0 rounded-full',
                                    dotClass[column.dot],
                                )}
                                aria-hidden
                            />
                            <h3 className="text-[10px] font-bold tracking-[0.18em] text-muted-foreground uppercase">
                                {column.label}
                            </h3>
                        </div>
                        <span className="text-xs font-semibold text-muted-foreground">
                            {columnTasks.length}
                        </span>
                    </header>
                    <ul className="flex flex-1 flex-col gap-2 p-2">
                        {columnTasks.length === 0 ? (
                            <li className="rounded-lg border border-dashed border-border/60 px-3 py-6 text-center text-xs text-muted-foreground">
                                {canDrag ? 'Drop tasks here' : 'No tasks'}
                            </li>
                        ) : (
                            columnTasks.map((task) => (
                                <li key={task.id}>
                                    <div
                                        draggable={canDrag}
                                        onDragStart={(event) =>
                                            handleDragStart(event, task.id)
                                        }
                                        onDragEnd={handleDragEnd}
                                        className={cn(
                                            'tcm-kanban-card',
                                            canDrag && 'tcm-kanban-card--draggable',
                                            draggingTaskId === task.id &&
                                                'tcm-kanban-card--dragging',
                                        )}
                                    >
                                        {canDrag && (
                                            <div
                                                className="mb-2 flex items-center gap-1 text-[10px] text-muted-foreground"
                                                aria-hidden
                                            >
                                                <GripVertical className="size-3.5 shrink-0 opacity-60" />
                                                <span>Drag to move</span>
                                            </div>
                                        )}
                                        <Link
                                            href={TaskController.show.url([
                                                organizationId,
                                                task.id,
                                            ])}
                                            draggable={false}
                                            onClick={(event) => {
                                                if (draggingTaskId !== null) {
                                                    event.preventDefault();
                                                }
                                            }}
                                            className={cn(
                                                'mb-2 block text-sm font-medium leading-snug hover:text-primary hover:underline',
                                                task.is_done &&
                                                    'text-muted-foreground line-through',
                                            )}
                                        >
                                            {task.title}
                                        </Link>
                                        <div className="mb-2 flex flex-wrap items-center gap-1.5">
                                            <StatusPill status={task.status} />
                                            {task.priority && (
                                                <span className="text-[10px] text-muted-foreground uppercase">
                                                    {task.priority}
                                                </span>
                                            )}
                                        </div>
                                        {task.assignees.length > 0 && (
                                            <p className="mb-2 truncate text-[11px] text-muted-foreground">
                                                {task.assignees
                                                    .map(
                                                        (assignee) =>
                                                            assignee.display_name ??
                                                            'Member',
                                                    )
                                                    .join(', ')}
                                            </p>
                                        )}
                                        {canUpdateStatus && isMobile && (
                                            <label className="flex flex-col gap-1">
                                                <span className="sr-only">
                                                    Move {task.title}
                                                </span>
                                                <select
                                                    value={task.status}
                                                    onChange={(event) => {
                                                        updateStatus(
                                                            task.id,
                                                            event.target
                                                                .value as TaskStatusValue,
                                                        );
                                                    }}
                                                    className="h-8 w-full rounded-md border border-input bg-background px-2 text-xs"
                                                >
                                                    {STATUS_OPTIONS.map(
                                                        (option) => (
                                                            <option
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                            </label>
                                        )}
                                    </div>
                                </li>
                            ))
                        )}
                    </ul>
                </section>
            ))}
        </div>
    );
}
