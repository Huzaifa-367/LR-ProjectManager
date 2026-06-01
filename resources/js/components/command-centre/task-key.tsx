export function formatTaskKey(taskId: number): string {
    return `TCM-${taskId}`;
}

export function TaskKey({
    taskId,
    className,
}: {
    taskId: number;
    className?: string;
}) {
    return (
        <span
            className={`font-mono text-[11px] text-muted-foreground ${className ?? ''}`}
        >
            {formatTaskKey(taskId)}
        </span>
    );
}
