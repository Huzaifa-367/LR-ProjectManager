import type { PropsWithChildren, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type PageStat = {
    label: string;
    value: number | string;
    tone?: 'default' | 'accent' | 'success' | 'warning';
};

type PageShellProps = PropsWithChildren<{
    title: ReactNode;
    accent?: ReactNode;
    subtitle?: string;
    stats?: PageStat[];
    actions?: ReactNode;
    className?: string;
    contentClassName?: string;
}>;

const statToneClass: Record<NonNullable<PageStat['tone']>, string> = {
    default: 'text-foreground',
    accent: 'text-primary',
    success: 'text-emerald-600 dark:text-emerald-400',
    warning: 'text-amber-600 dark:text-amber-400',
};

export function PageShell({
    title,
    accent,
    subtitle,
    stats = [],
    actions,
    className,
    contentClassName,
    children,
}: PageShellProps) {
    return (
        <div className={cn('tcm-page flex flex-col gap-6', className)}>
            <section className="flex flex-col gap-4 border-b border-border/60 pb-6 lg:flex-row lg:items-end lg:justify-between">
                <div className="min-w-0 space-y-1">
                    <h1 className="tcm-greeting">
                        {title}
                        {accent !== undefined && (
                            <>
                                {' '}
                                <span className="text-primary">{accent}</span>
                            </>
                        )}
                    </h1>
                    {subtitle && (
                        <p className="text-xs italic tracking-wide text-muted-foreground">
                            {subtitle}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap items-end gap-4">
                    {stats.length > 0 && (
                        <dl className="flex flex-wrap gap-x-6 gap-y-3">
                            {stats.map((stat) => (
                                <div key={stat.label} className="text-right">
                                    <dt className="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground/80">
                                        {stat.label}
                                    </dt>
                                    <dd
                                        className={cn(
                                            'font-semibold text-xl leading-none',
                                            statToneClass[
                                                stat.tone ?? 'default'
                                            ],
                                        )}
                                    >
                                        {stat.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    )}
                    {actions}
                </div>
            </section>
            <div className={cn('flex flex-col gap-6', contentClassName)}>
                {children}
            </div>
        </div>
    );
}
