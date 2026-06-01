import { cn } from '@/lib/utils';

type Assignee = {
    id: number;
    display_name: string | null;
};

function initialsFor(name: string | null, id: number): string {
    if (name === null || name.trim() === '') {
        return String(id).slice(-2);
    }

    const parts = name.trim().split(/\s+/);

    if (parts.length >= 2) {
        return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
    }

    return name.slice(0, 2).toUpperCase();
}

export function AssigneeAvatars({
    assignees,
    max = 3,
    className,
}: {
    assignees: Assignee[];
    max?: number;
    className?: string;
}) {
    if (assignees.length === 0) {
        return null;
    }

    const visible = assignees.slice(0, max);
    const overflow = assignees.length - visible.length;

    return (
        <div
            className={cn('flex items-center -space-x-1.5', className)}
            aria-label={assignees
                .map((a) => a.display_name ?? 'Member')
                .join(', ')}
        >
            {visible.map((assignee) => (
                <span
                    key={assignee.id}
                    className="flex size-6 shrink-0 items-center justify-center rounded-full border-2 border-card bg-muted text-[10px] font-semibold text-muted-foreground"
                    title={assignee.display_name ?? 'Member'}
                >
                    {initialsFor(assignee.display_name, assignee.id)}
                </span>
            ))}
            {overflow > 0 && (
                <span className="flex size-6 shrink-0 items-center justify-center rounded-full border-2 border-card bg-muted text-[10px] font-medium text-muted-foreground">
                    +{overflow}
                </span>
            )}
        </div>
    );
}
