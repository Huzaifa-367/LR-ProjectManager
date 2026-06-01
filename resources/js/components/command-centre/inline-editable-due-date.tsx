import { Check } from 'lucide-react';
import { useEffect, useState } from 'react';
import { TaskDueDateLabel } from '@/components/command-centre/task-deadline-label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    computeDueDateUi,
    dateTimeLocalToDeadlineDate,
    formatDueDateLabel,
    toDateTimeLocalValue,
} from '@/lib/task-options';
import { cn } from '@/lib/utils';

type InlineEditableDueDateProps = {
    value: string | null;
    canEdit: boolean;
    onSave: (value: string | null) => void;
};

export function InlineEditableDueDate({
    value,
    canEdit,
    onSave,
}: InlineEditableDueDateProps) {
    const [draft, setDraft] = useState(() => toDateTimeLocalValue(value));

    useEffect(() => {
        setDraft(toDateTimeLocalValue(value));
    }, [value]);

    const dueTone = computeDueDateUi(value);
    const label = formatDueDateLabel(value);

    const commit = (): void => {
        const trimmed = draft.trim();
        const next = trimmed === '' ? null : dateTimeLocalToDeadlineDate(trimmed);
        const currentLocal = value ? toDateTimeLocalValue(value) : '';

        if ((trimmed === '' ? '' : trimmed) !== currentLocal) {
            onSave(next);
        }
    };

    if (!canEdit) {
        return label ? (
            <TaskDueDateLabel
                deadlineDate={value}
                deadlineUi={dueTone}
                prefix=""
                className="text-sm"
            />
        ) : (
            <span className="text-sm text-muted-foreground">No due date</span>
        );
    }

    return (
        <div className="min-w-0 flex-1 space-y-2">
            <Input
                type="datetime-local"
                value={draft}
                onChange={(event) => setDraft(event.target.value)}
                className={cn(
                    'w-full',
                    dueTone === 'overdue' && 'border-destructive/50',
                    dueTone === 'soon' &&
                        'border-amber-500/50 dark:border-amber-400/50',
                )}
                aria-label="Due date and time"
            />
            {label && (
                <p
                    className={cn(
                        'text-xs text-muted-foreground',
                        dueTone === 'overdue' && 'text-destructive',
                        dueTone === 'soon' &&
                            'text-amber-600 dark:text-amber-400',
                    )}
                >
                    {label}
                </p>
            )}
            <div className="flex flex-wrap gap-1">
                <Button type="button" size="sm" onClick={commit}>
                    <Check className="size-4" />
                    Save
                </Button>
                {value && (
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => onSave(null)}
                    >
                        Clear
                    </Button>
                )}
            </div>
        </div>
    );
}
