import MemberDailyFocusController from '@/actions/App/Http/Controllers/CommandCentre/MemberDailyFocusController';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Link, router } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { TaskMetaLine } from '@/components/command-centre/task-deadline-label';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import type { FocusPin } from '@/types/command-centre';
import { cn } from '@/lib/utils';

type FocusPinRowProps = {
    organizationId: number;
    pin: FocusPin;
    index: number;
    canToggleDone: boolean;
    canUnpin: boolean;
};

export function FocusPinRow({
    organizationId,
    pin,
    index,
    canToggleDone,
    canUnpin,
}: FocusPinRowProps) {
    const { task } = pin;

    return (
        <li
            className={cn(
                'flex items-start gap-2.5 rounded-md px-2 py-2 transition-colors hover:bg-muted/40',
                task.is_done && 'opacity-60',
            )}
        >
            <span className="flex size-6 shrink-0 items-center justify-center rounded-md bg-primary/10 text-xs font-semibold text-primary tabular-nums">
                {index + 1}
            </span>
            <div className="min-w-0 flex-1">
                <div className="mb-0.5 flex flex-wrap items-center gap-1.5">
                    <Link
                        href={TaskController.show.url([
                            organizationId,
                            task.id,
                        ])}
                        className={cn(
                            'text-sm font-medium leading-snug hover:text-primary hover:underline',
                            task.is_done &&
                                'text-muted-foreground line-through',
                        )}
                    >
                        {task.title}
                    </Link>
                    {pin.is_auto && (
                        <Badge variant="outline" className="h-5 px-1.5 text-[10px]">
                            Auto
                        </Badge>
                    )}
                </div>
                <TaskMetaLine
                    projectName={task.project_name}
                    deadlineDate={task.deadline_date}
                    deadlineUi={task.deadline_ui}
                />
            </div>
            <div className="flex shrink-0 gap-0.5">
                {canToggleDone && !task.is_done && (
                    <Button
                        size="icon"
                        variant="ghost"
                        className="size-7"
                        title="Mark done"
                        onClick={() => {
                            router.patch(
                                TaskController.toggleDone.url([
                                    organizationId,
                                    task.id,
                                ]),
                            );
                        }}
                    >
                        <Check className="size-4" />
                    </Button>
                )}
                {canUnpin && (
                    <Button
                        size="icon"
                        variant="ghost"
                        className="size-7 text-muted-foreground"
                        title="Remove from focus"
                        onClick={() => {
                            router.delete(
                                MemberDailyFocusController.destroy.url([
                                    organizationId,
                                    pin.id,
                                ]),
                            );
                        }}
                    >
                        <X className="size-4" />
                    </Button>
                )}
            </div>
        </li>
    );
}
