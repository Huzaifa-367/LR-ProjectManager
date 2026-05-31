import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { EmptyState } from '@/components/command-centre/empty-state';
import { StatusPill } from '@/components/command-centre/status-pill';
import { TaskKindBadge, taskKindCardClass } from '@/components/command-centre/task-kind-badge';
import type { TasksListItem } from '@/components/command-centre/tasks-list-view';
import { Button } from '@/components/ui/button';
import { dueDateKey } from '@/lib/task-options';
import {
    formatDateKey,
    formatDayLabel,
    formatMonthYear,
    formatWeekRange,
    getMonthGrid,
    getWeekDays,
    isSameDay,
    navigateCalendarPeriod,
    parseDateKey,
    startOfDay,
    WEEKDAY_LABELS
} from '@/lib/task-calendar';
import type {CalendarPeriod} from '@/lib/task-calendar';
import { cn } from '@/lib/utils';

type TasksCalendarViewProps = {
    organizationId: number;
    tasks: TasksListItem[];
    period: CalendarPeriod;
};

type CalendarTask = TasksListItem & { deadlineDate: Date };

const MAX_VISIBLE_TASKS = 3;

function groupTasksByDate(tasks: TasksListItem[]): {
    scheduled: Map<string, CalendarTask[]>;
    unscheduled: TasksListItem[];
} {
    const scheduled = new Map<string, CalendarTask[]>();
    const unscheduled: TasksListItem[] = [];

    for (const task of tasks) {
        if (task.deadline_date === null) {
            unscheduled.push(task);
            continue;
        }

        const key = dueDateKey(task.deadline_date);
        const bucket = scheduled.get(key) ?? [];
        bucket.push({
            ...task,
            deadlineDate: parseDateKey(key),
        });
        scheduled.set(key, bucket);
    }

    for (const bucket of scheduled.values()) {
        bucket.sort((left, right) => left.title.localeCompare(right.title));
    }

    unscheduled.sort((left, right) => left.title.localeCompare(right.title));

    return { scheduled, unscheduled };
}

function CalendarTaskChip({
    organizationId,
    task,
    compact = false,
}: {
    organizationId: number;
    task: CalendarTask;
    compact?: boolean;
}) {
    return (
        <Link
            href={TaskController.show.url([organizationId, task.id])}
            className={cn(
                'tcm-calendar-task',
                taskKindCardClass(task.kind),
                task.is_done && 'tcm-calendar-task--done',
                compact && 'tcm-calendar-task--compact',
            )}
            title={task.title}
        >
            {!compact && <TaskKindBadge kind={task.kind} />}
            <span className="truncate">{task.title}</span>
        </Link>
    );
}

function DayTaskList({
    organizationId,
    date,
    tasks,
    showDateHeading = false,
}: {
    organizationId: number;
    date: Date;
    tasks: CalendarTask[];
    showDateHeading?: boolean;
}) {
    const dateKey = formatDateKey(date);
    const dayTasks = tasks.filter(
        (task) => formatDateKey(task.deadlineDate) === dateKey,
    );

    return (
        <div className="flex min-h-0 flex-1 flex-col gap-2">
            {showDateHeading && (
                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    {date.toLocaleDateString(undefined, {
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric',
                    })}
                </p>
            )}
            {dayTasks.length === 0 ? (
                <p className="rounded-lg border border-dashed border-border/60 px-3 py-4 text-center text-xs text-muted-foreground">
                    No due tasks
                </p>
            ) : (
                <ul className="flex flex-col gap-2">
                    {dayTasks.map((task) => (
                        <li
                            key={task.id}
                            className="rounded-lg border border-border/60 bg-card p-3"
                        >
                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                <TaskKindBadge kind={task.kind} />
                                <StatusPill status={task.status} />
                            </div>
                            <Link
                                href={TaskController.show.url([
                                    organizationId,
                                    task.id,
                                ])}
                                className={cn(
                                    'mb-1 block text-sm font-medium hover:text-primary hover:underline',
                                    task.is_done &&
                                        'text-muted-foreground line-through',
                                )}
                            >
                                {task.title}
                            </Link>
                            <p className="text-xs text-muted-foreground">
                                {task.project_name ?? 'Project'}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export function TasksCalendarView({
    organizationId,
    tasks,
    period,
}: TasksCalendarViewProps) {
    const [focusDate, setFocusDate] = useState(() => startOfDay(new Date()));
    const { scheduled, unscheduled } = useMemo(
        () => groupTasksByDate(tasks),
        [tasks],
    );
    const scheduledTasks = useMemo(
        () => Array.from(scheduled.values()).flat(),
        [scheduled],
    );
    const today = startOfDay(new Date());

    const periodLabel =
        period === 'month'
            ? formatMonthYear(focusDate)
            : period === 'week'
              ? formatWeekRange(focusDate)
              : formatDayLabel(focusDate);

    const movePeriod = (direction: -1 | 1): void => {
        setFocusDate((current) =>
            navigateCalendarPeriod(current, period, direction),
        );
    };

    if (tasks.length === 0) {
        return (
            <EmptyState>
                No tasks match your visibility scope.
            </EmptyState>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => movePeriod(-1)}
                        aria-label="Previous period"
                    >
                        <ChevronLeft className="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => movePeriod(1)}
                        aria-label="Next period"
                    >
                        <ChevronRight className="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => setFocusDate(today)}
                    >
                        Today
                    </Button>
                </div>
                <p className="text-sm font-semibold">{periodLabel}</p>
            </div>

            {period === 'month' && (
                <div className="tcm-calendar-month">
                    <div className="tcm-calendar-month-head">
                        {WEEKDAY_LABELS.map((label) => (
                            <div key={label} className="tcm-calendar-weekday">
                                {label}
                            </div>
                        ))}
                    </div>
                    <div className="tcm-calendar-month-grid">
                        {getMonthGrid(focusDate).map((day) => {
                            const key = formatDateKey(day);
                            const dayTasks = scheduled.get(key) ?? [];
                            const inCurrentMonth =
                                day.getMonth() === focusDate.getMonth();
                            const isToday = isSameDay(day, today);

                            return (
                                <div
                                    key={key}
                                    className={cn(
                                        'tcm-calendar-day',
                                        !inCurrentMonth &&
                                            'tcm-calendar-day--muted',
                                        isToday && 'tcm-calendar-day--today',
                                    )}
                                >
                                    <div className="mb-1 flex items-center justify-between gap-1">
                                        <span className="text-xs font-semibold">
                                            {day.getDate()}
                                        </span>
                                        {dayTasks.length > 0 && (
                                            <span className="text-[10px] text-muted-foreground">
                                                {dayTasks.length}
                                            </span>
                                        )}
                                    </div>
                                    <div className="flex min-h-0 flex-1 flex-col gap-1">
                                        {dayTasks
                                            .slice(0, MAX_VISIBLE_TASKS)
                                            .map((task) => (
                                                <CalendarTaskChip
                                                    key={task.id}
                                                    organizationId={
                                                        organizationId
                                                    }
                                                    task={task}
                                                    compact
                                                />
                                            ))}
                                        {dayTasks.length >
                                            MAX_VISIBLE_TASKS && (
                                            <span className="text-[10px] text-muted-foreground">
                                                +
                                                {dayTasks.length -
                                                    MAX_VISIBLE_TASKS}{' '}
                                                more
                                            </span>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {period === 'week' && (
                <div className="tcm-calendar-week">
                    {getWeekDays(focusDate).map((day) => {
                        const key = formatDateKey(day);
                        const dayTasks = scheduled.get(key) ?? [];
                        const isToday = isSameDay(day, today);

                        return (
                            <section
                                key={key}
                                className={cn(
                                    'tcm-calendar-week-column',
                                    isToday && 'tcm-calendar-week-column--today',
                                )}
                            >
                                <header className="tcm-calendar-week-column-head">
                                    <span className="text-[10px] font-bold tracking-[0.16em] text-muted-foreground uppercase">
                                        {day.toLocaleDateString(undefined, {
                                            weekday: 'short',
                                        })}
                                    </span>
                                    <span className="text-sm font-semibold">
                                        {day.getDate()}
                                    </span>
                                </header>
                                <ul className="flex flex-1 flex-col gap-1.5 overflow-y-auto p-2">
                                    {dayTasks.length === 0 ? (
                                        <li className="rounded-md border border-dashed border-border/60 px-2 py-4 text-center text-[11px] text-muted-foreground">
                                            No tasks
                                        </li>
                                    ) : (
                                        dayTasks.map((task) => (
                                            <li key={task.id}>
                                                <CalendarTaskChip
                                                    organizationId={
                                                        organizationId
                                                    }
                                                    task={task}
                                                />
                                            </li>
                                        ))
                                    )}
                                </ul>
                            </section>
                        );
                    })}
                </div>
            )}

            {period === 'day' && (
                <DayTaskList
                    organizationId={organizationId}
                    date={focusDate}
                    tasks={scheduledTasks}
                    showDateHeading={false}
                />
            )}

            {unscheduled.length > 0 && (
                <section className="rounded-xl border border-border/70 bg-muted/20 p-4">
                    <header className="mb-3 flex items-center justify-between gap-2">
                        <h3 className="text-[10px] font-bold tracking-[0.18em] text-muted-foreground uppercase">
                            No due date
                        </h3>
                        <span className="text-xs font-semibold text-muted-foreground">
                            {unscheduled.length}
                        </span>
                    </header>
                    <ul className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {unscheduled.map((task) => (
                            <li
                                key={task.id}
                                className="rounded-lg border border-border/60 bg-card p-3"
                            >
                                <div className="mb-2 flex flex-wrap items-center gap-2">
                                    <TaskKindBadge kind={task.kind} />
                                    <StatusPill status={task.status} />
                                </div>
                                <Link
                                    href={TaskController.show.url([
                                        organizationId,
                                        task.id,
                                    ])}
                                    className={cn(
                                        'mb-1 block text-sm font-medium hover:text-primary hover:underline',
                                        task.is_done &&
                                            'text-muted-foreground line-through',
                                    )}
                                >
                                    {task.title}
                                </Link>
                                <p className="text-xs text-muted-foreground">
                                    {task.project_name ?? 'Project'}
                                </p>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </div>
    );
}
