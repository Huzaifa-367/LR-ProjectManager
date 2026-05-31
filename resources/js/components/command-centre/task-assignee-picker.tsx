import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type TaskTeamMemberOption = {
    id: number;
    display_name: string | null;
    role_name?: string | null;
};

type TaskAssigneePickerProps = {
    members: TaskTeamMemberOption[];
    selectedIds: number[];
    onChange: (memberIds: number[]) => void;
    disabled?: boolean;
    emptyMessage?: string;
    className?: string;
};

export function TaskAssigneePicker({
    members,
    selectedIds,
    onChange,
    disabled = false,
    emptyMessage = 'No project team members available.',
    className,
}: TaskAssigneePickerProps) {
    if (members.length === 0) {
        return (
            <p className={cn('text-sm text-muted-foreground', className)}>
                {emptyMessage}
            </p>
        );
    }

    const toggleMember = (memberId: number, checked: boolean): void => {
        if (checked) {
            onChange(
                selectedIds.includes(memberId)
                    ? selectedIds
                    : [...selectedIds, memberId],
            );

            return;
        }

        onChange(selectedIds.filter((id) => id !== memberId));
    };

    return (
        <ul className={cn('space-y-2', className)}>
            {members.map((member) => {
                const isSelected = selectedIds.includes(member.id);

                return (
                    <li key={member.id}>
                        <label
                            className={cn(
                                'flex cursor-pointer items-center gap-3 rounded-lg border border-border/60 px-3 py-2 transition-colors',
                                isSelected && 'border-primary/30 bg-primary/5',
                                disabled && 'cursor-not-allowed opacity-60',
                            )}
                        >
                            <Checkbox
                                checked={isSelected}
                                disabled={disabled}
                                onCheckedChange={(checked) =>
                                    toggleMember(member.id, checked === true)
                                }
                            />
                            <span className="min-w-0 flex-1">
                                <span className="block text-sm font-medium">
                                    {member.display_name ?? 'Member'}
                                </span>
                                {member.role_name && (
                                    <span className="text-xs text-muted-foreground">
                                        {member.role_name}
                                    </span>
                                )}
                            </span>
                        </label>
                    </li>
                );
            })}
        </ul>
    );
}

type TaskAssigneeHiddenInputsProps = {
    selectedIds: number[];
    name?: string;
};

export function TaskAssigneeHiddenInputs({
    selectedIds,
    name = 'assignee_member_ids',
}: TaskAssigneeHiddenInputsProps) {
    return (
        <>
            {selectedIds.map((memberId, index) => (
                <input
                    key={memberId}
                    type="hidden"
                    name={`${name}[${index}]`}
                    value={memberId}
                />
            ))}
        </>
    );
}
