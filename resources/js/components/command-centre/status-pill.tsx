import { cn } from '@/lib/utils';

const statusClass: Record<string, string> = {
    pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    in_progress: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    done: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    blocked: 'bg-primary/10 text-primary',
    stuck: 'bg-primary/10 text-primary',
    hold: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
    on_hold: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
    follow_up: 'bg-teal-500/10 text-teal-700 dark:text-teal-300',
    active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    progressing: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    steady: 'bg-muted text-muted-foreground',
};

export function StatusPill({
    status,
    className,
}: {
    status: string;
    className?: string;
}) {
    const label = status.replace(/_/g, ' ');

    return (
        <span
            className={cn(
                'inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase',
                statusClass[status] ??
                    'bg-muted text-muted-foreground',
                className,
            )}
        >
            {label}
        </span>
    );
}
