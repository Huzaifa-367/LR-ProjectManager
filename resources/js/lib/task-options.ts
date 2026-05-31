import { parseDateKey, startOfDay } from '@/lib/task-calendar';

export type TaskKindValue = 'task' | 'decision' | 'reminder';

export const TASK_KINDS = [
    { value: 'task' as const, label: 'Task' },
    { value: 'decision' as const, label: 'Decision' },
    { value: 'reminder' as const, label: 'Reminder' },
];

export const TASK_STATUSES = [
    { value: 'pending', label: 'Pending' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'done', label: 'Done' },
    { value: 'stuck', label: 'Stuck' },
    { value: 'hold', label: 'On hold' },
    { value: 'follow_up', label: 'Follow up' },
] as const;

export const TASK_PRIORITIES = [
    { value: 'high', label: 'High' },
    { value: 'medium', label: 'Medium' },
    { value: 'low', label: 'Low' },
] as const;

export type DueDateUi = 'soon' | 'overdue' | 'week' | 'normal';

export function parseDueDate(value: string): Date {
    if (value.includes('T')) {
        return new Date(value);
    }

    if (value.includes(' ')) {
        return new Date(value.replace(' ', 'T'));
    }

    return parseDateKey(value);
}

export function dueDateKey(value: string): string {
    return value.slice(0, 10);
}

export function toDateTimeLocalValue(
    value: string | null | undefined,
): string {
    if (!value) {
        return '';
    }

    const date = parseDueDate(value);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

export function diffCalendarDays(from: Date, to: Date): number {
    const fromDay = startOfDay(from);
    const toDay = startOfDay(to);

    return Math.round(
        (toDay.getTime() - fromDay.getTime()) / (24 * 60 * 60 * 1000),
    );
}

function formatDueTime(date: Date): string {
    return date.toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });
}

function dueTimeSuffix(value: string, due: Date): string {
    if (!value.includes('T') && !value.includes(' ')) {
        return '';
    }

    return ` at ${formatDueTime(due)}`;
}

export function formatDueDateLabel(
    deadlineDate: string | null | undefined,
): string | null {
    if (!deadlineDate) {
        return null;
    }

    const due = parseDueDate(deadlineDate);
    const today = startOfDay(new Date());
    const diff = diffCalendarDays(today, due);
    const timeSuffix = dueTimeSuffix(deadlineDate, due);

    if (diff === 0) {
        return `Today${timeSuffix}`;
    }

    if (diff === 1) {
        return `Tomorrow${timeSuffix}`;
    }

    if (diff === -1) {
        return `Yesterday${timeSuffix}`;
    }

    if (diff < -1) {
        return `${Math.abs(diff)} days overdue${timeSuffix}`;
    }

    if (diff <= 6) {
        return `${due.toLocaleDateString(undefined, { weekday: 'long' })}${timeSuffix}`;
    }

    const includeYear = due.getFullYear() !== today.getFullYear();
    const dateLabel = due.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        ...(includeYear ? { year: 'numeric' as const } : {}),
    });

    return `${dateLabel}${timeSuffix}`;
}

export function computeDueDateUi(
    deadlineDate: string | null | undefined,
): DueDateUi {
    if (!deadlineDate) {
        return 'normal';
    }

    const due = parseDueDate(deadlineDate);
    const now = new Date();

    if (due.getTime() < now.getTime()) {
        return 'overdue';
    }

    const diff = diffCalendarDays(startOfDay(now), startOfDay(due));

    if (diff === 0) {
        return 'soon';
    }

    if (diff <= 7) {
        return 'week';
    }

    return 'normal';
}

export function formatTaskKind(kind: string): string {
    return TASK_KINDS.find((entry) => entry.value === kind)?.label ?? kind;
}
