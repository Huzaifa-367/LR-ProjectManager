import {
    computeDueDateUi,
    formatDueDateLabel,
    type DueDateUi,
} from '@/lib/task-options';
import { cn } from '@/lib/utils';

type TaskDueDateLabelProps = {
    deadlineDate: string | null | undefined;
    deadlineUi?: DueDateUi;
    prefix?: string;
    className?: string;
};

const dueDateToneClass: Record<DueDateUi, string> = {
    overdue: 'font-medium text-destructive',
    soon: 'font-medium text-amber-600 dark:text-amber-400',
    week: 'text-muted-foreground',
    normal: 'text-muted-foreground',
};

export function TaskDueDateLabel({
    deadlineDate,
    deadlineUi,
    prefix = 'Due',
    className,
}: TaskDueDateLabelProps) {
    const label = formatDueDateLabel(deadlineDate);

    if (!label) {
        return null;
    }

    const tone = deadlineUi ?? computeDueDateUi(deadlineDate);

    return (
        <span
            className={cn('text-xs', dueDateToneClass[tone], className)}
        >
            {prefix} {label}
        </span>
    );
}

type TaskMetaLineProps = {
    projectName: string | null | undefined;
    deadlineDate: string | null | undefined;
    deadlineUi?: DueDateUi;
    suffix?: string | null;
};

export function TaskMetaLine({
    projectName,
    deadlineDate,
    deadlineUi,
    suffix,
}: TaskMetaLineProps) {
    const dueLabel = formatDueDateLabel(deadlineDate);

    return (
        <p className="text-xs text-muted-foreground">
            {projectName ?? 'Project'}
            {dueLabel && (
                <>
                    {' · '}
                    <TaskDueDateLabel
                        deadlineDate={deadlineDate}
                        deadlineUi={deadlineUi}
                        className="inline"
                    />
                </>
            )}
            {suffix ? ` · ${suffix}` : null}
        </p>
    );
}
