import { Link } from '@inertiajs/react';
import type { PropsWithChildren, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type SettingsNavItem = {
    label: string;
    href: string;
    active?: boolean;
};

type SettingsShellProps = PropsWithChildren<{
    title: string;
    description: string;
    backHref?: string;
    backLabel?: string;
    nav?: SettingsNavItem[];
    actions?: ReactNode;
}>;

export function SettingsShell({
    title,
    description,
    backHref,
    backLabel = 'Settings',
    nav = [],
    actions,
    children,
}: SettingsShellProps) {
    return (
        <div className="tcm-page mx-auto max-w-3xl">
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-1">
                    <h1 className="tcm-greeting text-xl sm:text-2xl">{title}</h1>
                    <p className="text-sm text-muted-foreground">{description}</p>
                </div>
                <div className="flex flex-wrap items-center gap-3">
                    {backHref && (
                        <Link
                            href={backHref}
                            className="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                        >
                            {backLabel}
                        </Link>
                    )}
                    {actions}
                </div>
            </div>
            {nav.length > 0 && (
                <nav
                    className="mb-6 flex flex-wrap gap-2"
                    aria-label="Settings sections"
                >
                    {nav.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                item.active
                                    ? 'border-primary/30 bg-primary/10 text-primary'
                                    : 'border-border/80 text-muted-foreground hover:border-border hover:text-foreground',
                            )}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>
            )}
            <div className="flex flex-col gap-5">{children}</div>
        </div>
    );
}
