import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Link, router } from '@inertiajs/react';
import { Check, ChevronRight } from 'lucide-react';
import { PriorityIndicator } from '@/components/command-centre/priority-indicator';
import { StatusPill } from '@/components/command-centre/status-pill';
import { TaskDueDateLabel } from '@/components/command-centre/task-deadline-label';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
import type { CommandCentreTask } from '@/types/command-centre';
import { cn } from '@/lib/utils';

type AssignedToMeListProps = {
    organizationId: number;
    tasks: CommandCentreTask[];
    permissions: string[];
    onViewAllInQueue?: () => void;
    maxVisible?: number;
};

export function AssignedToMeList({
    organizationId,
    tasks,
    permissions,
    onViewAllInQueue,
    maxVisible = 8,
}: AssignedToMeListProps) {
    const canToggleDone = canOrg(permissions, 'org.tasks.toggle-done');
    const visible = tasks.slice(0, maxVisible);
    const hiddenCount = tasks.length - visible.length;

    if (tasks.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-border/70 bg-muted/15 px-3 py-5 text-center">
                <p className="text-sm text-muted-foreground">
                    You have no open tasks assigned to you.
                </p>
                <Button
                    asChild
                    variant="link"
                    size="sm"
                    className="mt-2 h-auto px-0"
                >
                    <Link
                        href={TaskController.index.url(organizationId)}
                    >
                        Browse all tasks
                    </Link>
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <ul className="max-h-[22rem] space-y-2 overflow-y-auto pr-0.5">
                {visible.map((task) => (
                    <li key={task.id}>
                        <AssignedTaskCard
                            organizationId={organizationId}
                            task={task}
                            canToggleDone={canToggleDone}
                        />
                    </li>
                ))}
            </ul>
            {onViewAllInQueue && (
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="w-full text-xs"
                    onClick={onViewAllInQueue}
                >
                    Show in work queue
                    {hiddenCount > 0 &&
                        ` · scroll for ${hiddenCount} more`}
                </Button>
            )}
        </div>
    );
}

function AssignedTaskCard({
    organizationId,
    task,
    canToggleDone,
}: {
    organizationId: number;
    task: CommandCentreTask;
    canToggleDone: boolean;
}) {
    const showDue = task.deadline_date !== null;

    return (
        <article
            className={cn(
                'group relative rounded-lg border border-border/60 bg-background transition-colors hover:border-border hover:shadow-sm',
                task.deadline_ui === 'overdue' &&
                    'border-destructive/30 bg-destructive/[0.03]',
                task.deadline_ui === 'soon' &&
                    'border-amber-500/25 bg-amber-500/[0.03]',
            )}
        >
            <div className="flex items-stretch gap-0">
                {canToggleDone && (
                    <button
                        type="button"
                        className="flex w-10 shrink-0 items-center justify-center rounded-l-lg border-r border-border/50 text-muted-foreground transition-colors hover:bg-muted/60 hover:text-primary"
                        title="Mark as done"
                        aria-label={`Mark "${task.title}" as done`}
                        onClick={() => {
                            router.patch(
                                TaskController.toggleDone.url([
                                    organizationId,
                                    task.id,
                                ]),
                                {},
                                { preserveScroll: true },
                            );
                        }}
                    >
                        <Check className="size-4" />
                    </button>
                )}
                <Link
                    href={TaskController.show.url([
                        organizationId,
                        task.id,
                    ])}
                    className="flex min-w-0 flex-1 flex-col gap-1.5 px-3 py-2.5 pr-2"
                >
                    <div className="flex items-start justify-between gap-2">
                        <p className="line-clamp-2 text-sm font-medium leading-snug group-hover:text-primary">
                            {task.title}
                        </p>
                        <ChevronRight className="size-4 shrink-0 text-muted-foreground/50 group-hover:text-muted-foreground" />
                    </div>
                    <div className="flex flex-wrap items-center gap-1.5">
                        <StatusPill
                            status={task.status}
                            className="text-[10px]"
                        />
                        {task.priority === 'high' && (
                            <PriorityIndicator priority={task.priority} />
                        )}
                    </div>
                    <p className="text-xs text-muted-foreground">
                        <span className="font-medium text-foreground/80">
                            {task.project_name ?? 'Project'}
                        </span>
                        {showDue && (
                            <>
                                <span aria-hidden> · </span>
                                <TaskDueDateLabel
                                    deadlineDate={task.deadline_date}
                                    deadlineUi={task.deadline_ui}
                                    className="inline text-xs"
                                />
                            </>
                        )}
                    </p>
                </Link>
            </div>
        </article>
    );
}
