import { ExpandableText } from '@/components/command-centre/expandable-text';
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

const kindStyles: Record<
    OnboardingPlanItemKind,
    { label: string; className: string }
> = {
    task: {
        label: 'Task',
        className:
            'border-blue-500/20 bg-blue-500/10 text-blue-700 dark:text-blue-300',
    },
    decision: {
        label: 'Decision',
        className:
            'border-amber-500/20 bg-amber-500/10 text-amber-800 dark:text-amber-300',
    },
    reminder: {
        label: 'Reminder',
        className:
            'border-emerald-500/20 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300',
    },
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
    const kindStyle = kindStyles[kind];
    const priorityLabel = formatLabel(priority);
    const deadlineLabel = formatLabel(deadlineType);

    return (
        <li className={cn('space-y-2 py-3 first:pt-0 last:pb-0', className)}>
            <div className="flex flex-wrap items-start gap-2">
                <Badge
                    variant="outline"
                    className={cn('text-[10px] uppercase', kindStyle.className)}
                >
                    {kindStyle.label}
                </Badge>
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
