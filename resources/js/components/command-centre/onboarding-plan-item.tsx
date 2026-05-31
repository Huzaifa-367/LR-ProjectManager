import { ExpandableText } from '@/components/command-centre/expandable-text';
import { TaskKindBadge } from '@/components/command-centre/task-kind-badge';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type OnboardingPlanItemKind = 'task' | 'decision' | 'reminder';

type OnboardingPlanItemProps = {
    kind: OnboardingPlanItemKind;
    title: string;
    description?: string | null;
    priority?: string | null;
    deadlineType?: string | null;
    className?: string;
};

function formatLabel(value?: string | null): string | null {
    if (!value || value === 'none') {
        return null;
    }

    return value.replace(/_/g, ' ');
}

export function OnboardingPlanItem({
    kind,
    title,
    description,
    priority,
    deadlineType,
    className,
}: OnboardingPlanItemProps) {
    const priorityLabel = formatLabel(priority);
    const deadlineLabel = formatLabel(deadlineType);

    return (
        <li className={cn('space-y-2 py-3 first:pt-0 last:pb-0', className)}>
            <div className="flex flex-wrap items-start gap-2">
                <TaskKindBadge kind={kind} />
                <p className="min-w-0 flex-1 text-sm font-semibold leading-snug">
                    {title}
                </p>
            </div>
            {(priorityLabel || deadlineLabel) && (
                <div className="flex flex-wrap gap-1.5 pl-0.5">
                    {priorityLabel && (
                        <Badge
                            variant="secondary"
                            className="text-[10px] capitalize"
                        >
                            {priorityLabel}
                        </Badge>
                    )}
                    {deadlineLabel && (
                        <Badge variant="outline" className="text-[10px] capitalize">
                            {deadlineLabel}
                        </Badge>
                    )}
                </div>
            )}
            {description && (
                <ExpandableText
                    text={description}
                    maxLength={180}
                    textClassName="text-xs leading-relaxed text-muted-foreground"
                />
            )}
        </li>
    );
}
