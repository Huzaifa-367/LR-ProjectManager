import { Badge } from '@/components/ui/badge';
import type { TaskKindValue } from '@/lib/task-options';
import { cn } from '@/lib/utils';

const kindMeta: Record<
    TaskKindValue,
    { label: string; badgeClass: string; cardClass: string }
> = {
    task: {
        label: 'Task',
        badgeClass:
            'border-blue-500/25 bg-blue-500/10 text-blue-700 dark:text-blue-300',
        cardClass: 'border-l-[3px] border-l-blue-500/70',
    },
    decision: {
        label: 'Decision',
        badgeClass:
            'border-amber-500/25 bg-amber-500/10 text-amber-800 dark:text-amber-300',
        cardClass: 'border-l-[3px] border-l-amber-500/70',
    },
    reminder: {
        label: 'Reminder',
        badgeClass:
            'border-emerald-500/25 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300',
        cardClass: 'border-l-[3px] border-l-emerald-500/70',
    },
};

const defaultMeta = kindMeta.task;

export function taskKindCardClass(kind: string): string {
    const meta = kindMeta[kind as TaskKindValue] ?? defaultMeta;

    return meta.cardClass;
}

type TaskKindBadgeProps = {
    kind: string;
    className?: string;
};

export function TaskKindBadge({ kind, className }: TaskKindBadgeProps) {
    const meta = kindMeta[kind as TaskKindValue] ?? defaultMeta;

    return (
        <Badge
            variant="outline"
            className={cn('text-[10px] uppercase', meta.badgeClass, className)}
        >
            {meta.label}
        </Badge>
    );
}

export function TaskKindLegend({ className }: { className?: string }) {
    return (
        <div className={cn('flex flex-wrap items-center gap-2', className)}>
            {(Object.keys(kindMeta) as TaskKindValue[]).map((kind) => (
                <TaskKindBadge key={kind} kind={kind} />
            ))}
        </div>
    );
}
