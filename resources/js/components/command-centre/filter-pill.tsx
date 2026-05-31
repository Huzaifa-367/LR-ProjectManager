import { cn } from '@/lib/utils';

type FilterPillProps = {
    label: string;
    active?: boolean;
    variant?: 'crimson' | 'blue';
    onClick?: () => void;
};

export function FilterPill({
    label,
    active = false,
    variant = 'crimson',
    onClick,
}: FilterPillProps) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'rounded-full border px-2.5 py-1 text-[11px] font-medium transition-colors',
                active
                    ? variant === 'blue'
                        ? 'border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-300'
                        : 'border-primary/30 bg-primary/10 text-primary'
                    : 'border-border/80 bg-muted/40 text-muted-foreground hover:border-border hover:text-foreground',
            )}
        >
            {label}
        </button>
    );
}
