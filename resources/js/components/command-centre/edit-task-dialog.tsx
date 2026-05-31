import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Form } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { SelectField } from '@/components/command-centre/select-field';
import { TaskKindBadge } from '@/components/command-centre/task-kind-badge';
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
import { Spinner } from '@/components/ui/spinner';
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
        external_link?: string | null;
    };
    trigger?: React.ReactNode;
};

export function EditTaskDialog({
    organizationId,
    task,
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
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
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
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                    disabled={processing}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Save changes
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
