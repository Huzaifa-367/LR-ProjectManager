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

export function formatTaskKind(kind: string): string {
    return TASK_KINDS.find((entry) => entry.value === kind)?.label ?? kind;
}
