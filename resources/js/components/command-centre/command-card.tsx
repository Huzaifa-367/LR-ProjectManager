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
    crimson: 'bg-primary shadow-[0_0_8px_color-mix(in_oklab,var(--primary)_40%,transparent)]',
    gold: 'bg-amber-500 shadow-[0_0_8px_rgba(212,168,67,0.35)]',
    blue: 'bg-blue-500 shadow-[0_0_8px_rgba(91,156,246,0.35)]',
    green: 'bg-emerald-500 shadow-[0_0_8px_rgba(46,204,113,0.35)]',
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
                'overflow-hidden rounded-xl border border-border/70 bg-card transition-colors hover:border-border',
                className,
            )}
        >
            <header className="flex items-center justify-between gap-3 border-b border-border/60 px-4 py-3.5 sm:px-[18px]">
                <div className="min-w-0 space-y-1">
                    <div className="flex items-center gap-2">
                        <span
                            className={cn('size-1.5 shrink-0 rounded-full', dotClass[dot])}
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
                    !noPadding && 'px-4 py-3 sm:px-[18px] sm:py-3.5',
                    contentClassName,
                )}
            >
                {children}
            </div>
        </section>
    );
}
