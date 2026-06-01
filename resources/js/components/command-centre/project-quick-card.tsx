import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import { Link } from '@inertiajs/react';
import { StatusPill } from '@/components/command-centre/status-pill';
import { cn } from '@/lib/utils';

type ProjectQuickCardProps = {
    organizationId: number;
    project: {
        id: number;
        name: string;
        progress_percent: number;
        health: string;
        next_action: string | null;
    };
    className?: string;
};

export function ProjectQuickCard({
    organizationId,
    project,
    className,
}: ProjectQuickCardProps) {
    return (
        <Link
            href={ProjectController.show.url([organizationId, project.id])}
            className={cn(
                'group flex min-w-[14rem] flex-col gap-2 rounded-lg border border-border/60 bg-card p-3 transition-colors hover:border-border hover:bg-muted/30',
                className,
            )}
        >
            <div className="flex items-start justify-between gap-2">
                <p className="line-clamp-1 text-sm font-medium group-hover:text-primary">
                    {project.name}
                </p>
                <StatusPill
                    status={project.health}
                    className="shrink-0 text-[10px]"
                />
            </div>
            <div className="tcm-proj-bar-track">
                <div
                    className="tcm-proj-bar-fill"
                    style={{ width: `${project.progress_percent}%` }}
                />
            </div>
            <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span className="tabular-nums font-medium">
                    {project.progress_percent}%
                </span>
                {project.next_action && (
                    <span className="line-clamp-1 max-w-[8rem] text-right">
                        {project.next_action}
                    </span>
                )}
            </div>
        </Link>
    );
}
