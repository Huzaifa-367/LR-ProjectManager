import { ArrowDown, ArrowUp, Minus } from 'lucide-react';
import { cn } from '@/lib/utils';

const priorityConfig: Record<
    string,
    { label: string; icon: typeof ArrowUp; className: string }
> = {
    high: {
        label: 'High priority',
        icon: ArrowUp,
        className: 'text-destructive',
    },
    medium: {
        label: 'Medium priority',
        icon: Minus,
        className: 'text-amber-600 dark:text-amber-400',
    },
    low: {
        label: 'Low priority',
        icon: ArrowDown,
        className: 'text-blue-600 dark:text-blue-400',
    },
};

export function PriorityIndicator({
    priority,
    showLabel = false,
    className,
}: {
    priority: string | null;
    showLabel?: boolean;
    className?: string;
}) {
    if (priority === null || priority === '') {
        return null;
    }

    const config = priorityConfig[priority];

    if (config === undefined) {
        return null;
    }

    const Icon = config.icon;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 text-xs text-muted-foreground',
                className,
            )}
            title={config.label}
        >
            <Icon className={cn('size-3.5 shrink-0', config.className)} aria-hidden />
            {showLabel && (
                <span className="capitalize">{priority}</span>
            )}
        </span>
    );
}
