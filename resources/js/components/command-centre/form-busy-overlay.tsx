import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type FormBusyOverlayProps = {
    visible: boolean;
    title: string;
    description?: string;
    className?: string;
};

export function FormBusyOverlay({
    visible,
    title,
    description,
    className,
}: FormBusyOverlayProps) {
    if (!visible) {
        return null;
    }

    return (
        <div
            className={cn(
                'fixed inset-0 z-50 flex items-center justify-center bg-background/75 p-4 backdrop-blur-[2px]',
                className,
            )}
            role="status"
            aria-live="polite"
            aria-busy="true"
        >
            <div className="flex max-w-sm flex-col items-center gap-3 rounded-xl border border-border/70 bg-card px-8 py-7 text-center shadow-lg">
                <Spinner className="size-8 text-primary" />
                <div className="space-y-1">
                    <p className="text-sm font-semibold">{title}</p>
                    {description && (
                        <p className="text-xs text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
