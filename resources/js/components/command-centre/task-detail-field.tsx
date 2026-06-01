import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function TaskDetailField({
    label,
    children,
    className,
}: {
    label: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('space-y-1', className)}>
            <dt className="text-xs font-medium text-muted-foreground">
                {label}
            </dt>
            <dd className="text-sm text-foreground">{children}</dd>
        </div>
    );
}
