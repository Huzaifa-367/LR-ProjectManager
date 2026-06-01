import type { PropsWithChildren, ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type PageStat = {
    label: string;
    value: number | string;
    tone?: 'default' | 'accent' | 'success' | 'warning';
};

type PageShellProps = PropsWithChildren<{
    title: ReactNode;
    accent?: ReactNode;
    subtitle?: string;
    breadcrumbs?: BreadcrumbItem[];
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
    breadcrumbs = [],
    stats = [],
    actions,
    className,
    contentClassName,
    children,
}: PageShellProps) {
    return (
        <div className={cn('tcm-page flex flex-col gap-6', className)}>
            <section className="flex flex-col gap-4 border-b border-border/60 pb-5 lg:flex-row lg:items-end lg:justify-between">
                <div className="min-w-0 space-y-2">
                    {breadcrumbs.length > 0 && (
                        <nav
                            aria-label="Breadcrumb"
                            className="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            {breadcrumbs.map((item, index) => {
                                const isLast =
                                    index === breadcrumbs.length - 1;

                                return (
                                    <span
                                        key={`${item.href}-${index}`}
                                        className="inline-flex items-center gap-1.5"
                                    >
                                        {index > 0 && (
                                            <span
                                                className="text-border"
                                                aria-hidden
                                            >
                                                /
                                            </span>
                                        )}
                                        {isLast ? (
                                            <span className="font-medium text-foreground">
                                                {item.title}
                                            </span>
                                        ) : (
                                            <Link
                                                href={item.href}
                                                className="hover:text-foreground hover:underline"
                                            >
                                                {item.title}
                                            </Link>
                                        )}
                                    </span>
                                );
                            })}
                        </nav>
                    )}
                    <h1 className="tcm-greeting">
                        {title}
                        {accent !== undefined && (
                            <>
                                {' '}
                                <span className="text-primary">{accent}</span>
                            </>
                        )}
                    </h1>
                    {subtitle && breadcrumbs.length === 0 && (
                        <p className="tcm-section-eyebrow">{subtitle}</p>
                    )}
                </div>
                <div className="flex flex-wrap items-end gap-3">
                    {stats.length > 0 && (
                        <dl className="flex flex-wrap gap-2">
                            {stats.map((stat) => (
                                <div key={stat.label} className="tcm-stat-chip">
                                    <dt>{stat.label}</dt>
                                    <dd
                                        className={cn(
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
