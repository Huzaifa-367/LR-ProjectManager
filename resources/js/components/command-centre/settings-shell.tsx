import { Link } from '@inertiajs/react';
import type { PropsWithChildren, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
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
    const hasNav = nav.length > 0;

    return (
        <div className="mx-auto w-full px-4 py-6 sm:px-6">
            <div
                className={cn(
                    'mx-auto flex w-full flex-col gap-6',
                    hasNav &&
                        'lg:w-[62.5rem] lg:max-w-full lg:flex-row lg:items-start lg:gap-10',
                    !hasNav && 'lg:max-w-3xl',
                )}
            >
                {hasNav && (
                    <aside className="w-full shrink-0 lg:w-48">
                        <nav
                            className="flex flex-col space-y-1"
                            aria-label="Organization settings"
                        >
                            {nav.map((item) => (
                                <Button
                                    key={item.href}
                                    size="sm"
                                    variant="ghost"
                                    asChild
                                    className={cn(
                                        'w-full justify-start font-normal',
                                        item.active &&
                                            'bg-muted font-medium text-foreground',
                                    )}
                                >
                                    <Link href={item.href}>{item.label}</Link>
                                </Button>
                            ))}
                        </nav>
                    </aside>
                )}

                {hasNav && <Separator className="lg:hidden" />}

                <div
                    className={cn(
                        'min-w-0 w-full shrink-0',
                        hasNav ? 'lg:w-[48rem]' : 'lg:max-w-3xl',
                    )}
                >
                    <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-1">
                            <h1 className="tcm-greeting text-xl sm:text-2xl">
                                {title}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                            {backHref && (
                                <Link
                                    href={backHref}
                                    className="inline-block pt-1 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                                >
                                    {backLabel}
                                </Link>
                            )}
                        </div>
                        {actions ? (
                            <div className="flex shrink-0 flex-wrap items-center gap-2">
                                {actions}
                            </div>
                        ) : null}
                    </div>

                    <section className="flex w-full min-w-0 flex-col gap-5 overflow-x-auto">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
