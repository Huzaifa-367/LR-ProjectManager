import { Check } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import {
    TaskAssigneePicker,
    type TaskTeamMemberOption,
} from '@/components/command-centre/task-assignee-picker';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type TaskEditableAssigneesProps = {
    selectedIds: number[];
    members: TaskTeamMemberOption[];
    canEdit: boolean;
    onSave: (assigneeMemberIds: number[]) => void;
};

function idsEqual(a: number[], b: number[]): boolean {
    if (a.length !== b.length) {
        return false;
    }

    const sortedA = [...a].sort((x, y) => x - y);
    const sortedB = [...b].sort((x, y) => x - y);

    return sortedA.every((id, index) => id === sortedB[index]);
}

export function TaskEditableAssignees({
    selectedIds,
    members,
    canEdit,
    onSave,
}: TaskEditableAssigneesProps) {
    const [draftIds, setDraftIds] = useState<number[]>(selectedIds);
    const isDirty = useMemo(
        () => !idsEqual(draftIds, selectedIds),
        [draftIds, selectedIds],
    );

    useEffect(() => {
        setDraftIds(selectedIds);
    }, [selectedIds]);

    if (!canEdit || members.length === 0) {
        return null;
    }

    return (
        <div className="space-y-3">
            <TaskAssigneePicker
                members={members}
                selectedIds={draftIds}
                onChange={setDraftIds}
            />
            <Button
                type="button"
                size="sm"
                variant={isDirty ? 'default' : 'secondary'}
                className={cn('w-full', !isDirty && 'opacity-70')}
                disabled={!isDirty}
                onClick={() => onSave(draftIds)}
            >
                <Check className="size-4" />
                {isDirty ? 'Save assignees' : 'No changes'}
            </Button>
        </div>
    );
}
