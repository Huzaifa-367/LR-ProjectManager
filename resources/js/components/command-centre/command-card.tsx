import type { PropsWithChildren, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type CommandCardProps = PropsWithChildren<{
    title: string;
    description?: string;
    badge?: ReactNode;
    action?: ReactNode;
    dot?: 'crimson' | 'gold' | 'blue' | 'green';
    className?: string;
    contentClassName?: string;
    noPadding?: boolean;
}>;

const dotClass: Record<NonNullable<CommandCardProps['dot']>, string> = {
    crimson: 'bg-primary',
    gold: 'bg-amber-500',
    blue: 'bg-blue-500',
    green: 'bg-emerald-500',
};

export function CommandCard({
    title,
    description,
    badge,
    action,
    dot = 'crimson',
    className,
    contentClassName,
    noPadding = false,
    children,
}: CommandCardProps) {
    return (
        <section
            className={cn(
                'overflow-hidden rounded-lg border border-border/70 bg-card shadow-sm',
                className,
            )}
        >
            <header className="flex items-center justify-between gap-3 border-b border-border/60 bg-muted/20 px-4 py-3 sm:px-5">
                <div className="min-w-0 space-y-0.5">
                    <div className="flex items-center gap-2">
                        <span
                            className={cn(
                                'size-2 shrink-0 rounded-sm',
                                dotClass[dot],
                            )}
                            aria-hidden
                        />
                        <h2 className="tcm-card-title">{title}</h2>
                        {badge}
                    </div>
                    {description && (
                        <p className="text-xs text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                {action}
            </header>
            <div
                className={cn(
                    !noPadding && 'px-4 py-3 sm:px-5 sm:py-4',
                    contentClassName,
                )}
            >
                {children}
            </div>
        </section>
    );
}
