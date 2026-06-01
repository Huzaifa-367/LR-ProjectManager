import type { PropsWithChildren, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type CommandCentreSectionProps = PropsWithChildren<{
    title: string;
    count?: number;
    description?: string;
    action?: ReactNode;
    className?: string;
}>;

export function CommandCentreSection({
    title,
    count,
    description,
    action,
    className,
    children,
}: CommandCentreSectionProps) {
    return (
        <section className={cn('space-y-3', className)}>
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <h3 className="text-sm font-semibold text-foreground">
                        {title}
                        {count !== undefined && (
                            <span className="ml-1.5 font-normal text-muted-foreground">
                                {count}
                            </span>
                        )}
                    </h3>
                    {description && (
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                {action}
            </div>
            {children}
        </section>
    );
}
