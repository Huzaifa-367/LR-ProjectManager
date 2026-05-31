import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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

type CreateProjectDialogProps = {
    organizationId: number;
    trigger?: React.ReactNode;
};

export function CreateProjectDialog({
    organizationId,
    trigger,
}: CreateProjectDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm">
                        <Plus className="size-4" />
                        New project
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Create project</DialogTitle>
                    <DialogDescription>
                        Sets up default roles and adds you as project owner.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...ProjectController.store.form(organizationId)}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="create-project-name">Name</Label>
                                <Input
                                    id="create-project-name"
                                    name="name"
                                    required
                                    autoFocus
                                    placeholder="Q3 launch"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="create-project-objective">
                                    Objective
                                </Label>
                                <Input
                                    id="create-project-objective"
                                    name="objective"
                                    placeholder="What does success look like?"
                                />
                                <InputError message={errors.objective} />
                            </div>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <SubmitButton processing={processing}>
                                    Create project
                                </SubmitButton>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
