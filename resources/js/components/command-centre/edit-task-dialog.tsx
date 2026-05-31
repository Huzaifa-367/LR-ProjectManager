import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Form } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { SelectField } from '@/components/command-centre/select-field';
import { TaskKindBadge } from '@/components/command-centre/task-kind-badge';
import {
    TaskAssigneeHiddenInputs,
    TaskAssigneePicker,
    type TaskTeamMemberOption,
} from '@/components/command-centre/task-assignee-picker';
import { TaskDueDateField } from '@/components/command-centre/task-due-date-field';
import { SubmitButton } from '@/components/submit-button';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { TASK_PRIORITIES, TASK_STATUSES } from '@/lib/task-options';

type EditTaskDialogProps = {
    organizationId: number;
    task: {
        id: number;
        kind: string;
        title: string;
        description: string | null;
        status: string;
        priority?: string | null;
        deadline_date?: string | null;
        external_link?: string | null;
        assignees?: Array<{ id: number }>;
    };
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
    const [selectedAssigneeIds, setSelectedAssigneeIds] = useState<number[]>(
        task.assignees?.map((assignee) => assignee.id) ?? [],
    );

    const resetAssignees = (): void => {
        setSelectedAssigneeIds(
            task.assignees?.map((assignee) => assignee.id) ?? [],
        );
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                setOpen(nextOpen);

                if (nextOpen) {
                    resetAssignees();
                }
            }}
        >
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
                <Form
                    {...TaskController.update.form([organizationId, task.id])}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-task-title">Title</Label>
                                <Input
                                    id="edit-task-title"
                                    name="title"
                                    defaultValue={task.title}
                                    required
                                    autoFocus
                                    disabled={processing}
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-task-description">
                                    Description
                                </Label>
                                <Textarea
                                    id="edit-task-description"
                                    name="description"
                                    defaultValue={task.description ?? ''}
                                    rows={4}
                                    disabled={processing}
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <SelectField
                                    id="edit-task-status"
                                    name="status"
                                    label="Status"
                                    options={TASK_STATUSES}
                                    defaultValue={task.status}
                                    disabled={processing}
                                    error={errors.status}
                                />
                                <SelectField
                                    id="edit-task-priority"
                                    name="priority"
                                    label="Priority"
                                    options={[
                                        { value: '', label: 'None' },
                                        ...TASK_PRIORITIES,
                                    ]}
                                    defaultValue={task.priority ?? ''}
                                    disabled={processing}
                                    error={errors.priority}
                                />
                            </div>

                            <TaskDueDateField
                                id="edit-task-due-date"
                                defaultValue={task.deadline_date}
                                disabled={processing}
                                error={errors.deadline_date}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="edit-task-link">
                                    External link
                                </Label>
                                <Input
                                    id="edit-task-link"
                                    name="external_link"
                                    type="url"
                                    defaultValue={task.external_link ?? ''}
                                    placeholder="https://"
                                    disabled={processing}
                                />
                                <InputError message={errors.external_link} />
                            </div>

                            {canSyncAssignees && teamMembers.length > 0 && (
                                <div className="grid gap-2">
                                    <Label>Assignees</Label>
                                    <TaskAssigneePicker
                                        members={teamMembers}
                                        selectedIds={selectedAssigneeIds}
                                        onChange={setSelectedAssigneeIds}
                                        disabled={processing}
                                    />
                                    <TaskAssigneeHiddenInputs
                                        selectedIds={selectedAssigneeIds}
                                    />
                                    <InputError
                                        message={errors.assignee_member_ids}
                                    />
                                </div>
                            )}

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                    disabled={processing}
                                >
                                    Cancel
                                </Button>
                                <SubmitButton processing={processing}>
                                    Save changes
                                </SubmitButton>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
