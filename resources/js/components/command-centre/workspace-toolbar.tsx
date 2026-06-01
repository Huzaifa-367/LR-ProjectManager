import type { PropsWithChildren } from 'react';
import { cn } from '@/lib/utils';

export function WorkspaceToolbar({
    children,
    className,
}: PropsWithChildren<{ className?: string }>) {
    return (
        <div
            className={cn(
                'flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border/60 bg-muted/25 px-3 py-2.5',
                className,
            )}
        >
            {children}
        </div>
    );
}
