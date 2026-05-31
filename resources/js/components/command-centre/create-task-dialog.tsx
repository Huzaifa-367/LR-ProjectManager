import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
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
import { useOrganizationContext } from '@/hooks/use-organization-context';

type CreateTaskDialogProps = {
    organizationId: number;
    projects: Array<{ id: number; name: string }>;
    defaultProjectId?: number;
    trigger?: React.ReactNode;
};

export function CreateTaskDialog({
    organizationId,
    projects,
    defaultProjectId,
    trigger,
}: CreateTaskDialogProps) {
    const { selectedProject } = useOrganizationContext();
    const [open, setOpen] = useState(false);
    const resolvedProjectId =
        defaultProjectId ??
        selectedProject?.id ??
        projects[0]?.id ??
        undefined;

    if (projects.length === 0) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm">
                        <Plus className="size-4" />
                        New task
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Create task</DialogTitle>
                    <DialogDescription>
                        Assignees must already be on the project team.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...TaskController.store.form(organizationId)}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="kind" value="task" />
                            <div className="grid gap-2">
                                <Label htmlFor="create-task-project">Project</Label>
                                <select
                                    id="create-task-project"
                                    name="project_id"
                                    required
                                    defaultValue={resolvedProjectId}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    {projects.map((project) => (
                                        <option key={project.id} value={project.id}>
                                            {project.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.project_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="create-task-title">Title</Label>
                                <Input
                                    id="create-task-title"
                                    name="title"
                                    required
                                    autoFocus
                                    placeholder="Follow up with vendor"
                                />
                                <InputError message={errors.title} />
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
                                    Create task
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
