import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { SelectField } from '@/components/command-centre/select-field';
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
import { useOrganizationContext } from '@/hooks/use-organization-context';
import { TASK_KINDS, TASK_PRIORITIES, TASK_STATUSES } from '@/lib/task-options';

type CreateTaskDialogProps = {
    organizationId: number;
    projects: Array<{ id: number; name: string }>;
    defaultProjectId?: number;
    teamMembers?: TaskTeamMemberOption[];
    projectTeams?: Record<number, TaskTeamMemberOption[]>;
    trigger?: React.ReactNode;
};

export function CreateTaskDialog({
    organizationId,
    projects,
    defaultProjectId,
    teamMembers = [],
    projectTeams = {},
    trigger,
}: CreateTaskDialogProps) {
    const { selectedProject } = useOrganizationContext();
    const [open, setOpen] = useState(false);
    const [projectId, setProjectId] = useState<number | undefined>(
        defaultProjectId ??
            selectedProject?.id ??
            projects[0]?.id ??
            undefined,
    );
    const [selectedAssigneeIds, setSelectedAssigneeIds] = useState<number[]>(
        [],
    );

    const resolvedProjectId =
        projectId ?? defaultProjectId ?? projects[0]?.id ?? undefined;

    const assigneeOptions = useMemo(() => {
        if (teamMembers.length > 0) {
            return teamMembers;
        }

        if (resolvedProjectId !== undefined) {
            return projectTeams[resolvedProjectId] ?? [];
        }

        return [];
    }, [teamMembers, projectTeams, resolvedProjectId]);

    if (projects.length === 0) {
        return null;
    }

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                setOpen(nextOpen);

                if (!nextOpen) {
                    setSelectedAssigneeIds([]);
                }
            }}
        >
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm">
                        <Plus className="size-4" />
                        New work item
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Create work item</DialogTitle>
                    <DialogDescription>
                        Add a task, decision, or reminder to the project team
                        board.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...TaskController.store.form(organizationId)}
                    resetOnSuccess
                    onSuccess={() => {
                        setOpen(false);
                        setSelectedAssigneeIds([]);
                    }}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="create-task-project">Project</Label>
                                <select
                                    id="create-task-project"
                                    name="project_id"
                                    required
                                    value={resolvedProjectId ?? ''}
                                    disabled={processing}
                                    onChange={(event) => {
                                        setProjectId(
                                            Number.parseInt(
                                                event.target.value,
                                                10,
                                            ),
                                        );
                                        setSelectedAssigneeIds([]);
                                    }}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    {projects.map((project) => (
                                        <option
                                            key={project.id}
                                            value={project.id}
                                        >
                                            {project.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.project_id} />
                            </div>

                            <SelectField
                                id="create-task-kind"
                                name="kind"
                                label="Type"
                                options={TASK_KINDS}
                                defaultValue="task"
                                required
                                disabled={processing}
                                error={errors.kind}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="create-task-title">Title</Label>
                                <Input
                                    id="create-task-title"
                                    name="title"
                                    required
                                    autoFocus
                                    disabled={processing}
                                    placeholder="Follow up with vendor"
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="create-task-description">
                                    Description
                                </Label>
                                <Textarea
                                    id="create-task-description"
                                    name="description"
                                    rows={3}
                                    disabled={processing}
                                    placeholder="Optional context or acceptance criteria"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <SelectField
                                    id="create-task-status"
                                    name="status"
                                    label="Status"
                                    options={TASK_STATUSES}
                                    defaultValue="pending"
                                    disabled={processing}
                                    error={errors.status}
                                />
                                <SelectField
                                    id="create-task-priority"
                                    name="priority"
                                    label="Priority"
                                    options={[
                                        { value: '', label: 'None' },
                                        ...TASK_PRIORITIES,
                                    ]}
                                    defaultValue=""
                                    disabled={processing}
                                    error={errors.priority}
                                />
                            </div>

                            <TaskDueDateField
                                id="create-task-due-date"
                                disabled={processing}
                                error={errors.deadline_date}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="create-task-link">
                                    External link
                                </Label>
                                <Input
                                    id="create-task-link"
                                    name="external_link"
                                    type="url"
                                    disabled={processing}
                                    placeholder="https://"
                                />
                                <InputError message={errors.external_link} />
                            </div>

                            {assigneeOptions.length > 0 && (
                                <div className="grid gap-2">
                                    <Label>Assignees</Label>
                                    <TaskAssigneePicker
                                        members={assigneeOptions}
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
                                    Create
                                </SubmitButton>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
