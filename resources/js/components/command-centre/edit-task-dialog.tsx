import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Form } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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

const TASK_STATUSES = [
    { value: 'pending', label: 'Pending' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'done', label: 'Done' },
    { value: 'stuck', label: 'Stuck' },
    { value: 'hold', label: 'On hold' },
    { value: 'follow_up', label: 'Follow up' },
] as const;

type EditTaskDialogProps = {
    organizationId: number;
    task: {
        id: number;
        title: string;
        description: string | null;
        status: string;
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
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit task</DialogTitle>
                    <DialogDescription>
                        Update title, description, or status.
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
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-task-status">Status</Label>
                                <select
                                    id="edit-task-status"
                                    name="status"
                                    defaultValue={task.status}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    {TASK_STATUSES.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.status} />
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
                                />
                                <InputError message={errors.external_link} />
                            </div>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
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
