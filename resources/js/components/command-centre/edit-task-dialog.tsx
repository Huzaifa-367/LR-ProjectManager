import { Pencil } from 'lucide-react';
import { useState } from 'react';
import {
    EditTaskForm,
    type EditTaskFormTask,
} from '@/components/command-centre/edit-task-form';
import type { TaskTeamMemberOption } from '@/components/command-centre/task-assignee-picker';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { TaskKindBadge } from '@/components/command-centre/task-kind-badge';

type EditTaskDialogProps = {
    organizationId: number;
    task: EditTaskFormTask;
    teamMembers?: TaskTeamMemberOption[];
    canSyncAssignees?: boolean;
    trigger?: React.ReactNode;
};

export function EditTaskDialog({
    organizationId,
    task,
    teamMembers = [],
    canSyncAssignees = false,
    trigger,
}: EditTaskDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button variant="outline" size="sm">
                        <Pencil className="size-4" />
                        Edit
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit work item</DialogTitle>
                    <DialogDescription className="flex flex-wrap items-center gap-2">
                        <TaskKindBadge kind={task.kind} />
                        <span>Update details and workflow status.</span>
                    </DialogDescription>
                </DialogHeader>
                <EditTaskForm
                    organizationId={organizationId}
                    task={task}
                    teamMembers={teamMembers}
                    canSyncAssignees={canSyncAssignees}
                    showKindBadge={false}
                    onCancel={() => setOpen(false)}
                    onSuccess={() => setOpen(false)}
                />
            </DialogContent>
        </Dialog>
    );
}

// Re-export types for consumers
export type { EditTaskFormTask };
