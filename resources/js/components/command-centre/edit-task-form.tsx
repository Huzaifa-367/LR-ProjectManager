import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Form } from '@inertiajs/react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { TASK_PRIORITIES, TASK_STATUSES } from '@/lib/task-options';

export type EditTaskFormTask = {
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

type EditTaskFormProps = {
    organizationId: number;
    task: EditTaskFormTask;
    teamMembers?: TaskTeamMemberOption[];
    canSyncAssignees?: boolean;
    onCancel?: () => void;
    onSuccess?: () => void;
    showKindBadge?: boolean;
};

export function EditTaskForm({
    organizationId,
    task,
    teamMembers = [],
    canSyncAssignees = false,
    onCancel,
    onSuccess,
    showKindBadge = true,
}: EditTaskFormProps) {
    const [selectedAssigneeIds, setSelectedAssigneeIds] = useState<number[]>(
        () => task.assignees?.map((assignee) => assignee.id) ?? [],
    );

    return (
        <Form
            {...TaskController.update.form([organizationId, task.id])}
            onSuccess={() => onSuccess?.()}
            className="grid gap-5"
            key={`${task.id}-${task.title}-${task.status}-${task.deadline_date ?? ''}`}
        >
            {({ processing, errors }) => (
                <>
                    {showKindBadge && (
                        <div className="flex flex-wrap items-center gap-2">
                            <TaskKindBadge kind={task.kind} />
                            <span className="text-xs text-muted-foreground">
                                Changes save to this work item.
                            </span>
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="edit-task-title">Title</Label>
                        <Input
                            id="edit-task-title"
                            name="title"
                            defaultValue={task.title}
                            required
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
                            rows={6}
                            disabled={processing}
                            placeholder="Add context, steps, or acceptance criteria…"
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
                        <Label htmlFor="edit-task-link">External link</Label>
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
                        <div className="grid gap-2 rounded-lg border border-border/60 bg-muted/20 p-3">
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
                            <InputError message={errors.assignee_member_ids} />
                        </div>
                    )}

                    <div className="flex flex-wrap items-center justify-end gap-2 border-t border-border/60 pt-4">
                        {onCancel && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={onCancel}
                                disabled={processing}
                            >
                                Cancel
                            </Button>
                        )}
                        <SubmitButton processing={processing}>
                            Save changes
                        </SubmitButton>
                    </div>
                </>
            )}
        </Form>
    );
}
