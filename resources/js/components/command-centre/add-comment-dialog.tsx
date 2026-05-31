import TaskCommentController from '@/actions/App/Http/Controllers/CommandCentre/TaskCommentController';
import { Form } from '@inertiajs/react';
import { MessageSquarePlus } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type AddCommentDialogProps = {
    organizationId: number;
    taskId: number;
    trigger?: React.ReactNode;
};

export function AddCommentDialog({
    organizationId,
    taskId,
    trigger,
}: AddCommentDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm" variant="outline">
                        <MessageSquarePlus className="size-4" />
                        Add comment
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add comment</DialogTitle>
                    <DialogDescription>
                        Share an update on this task.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...TaskCommentController.store.form({
                        organization: organizationId,
                        task: taskId,
                    })}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="comment-body">Comment</Label>
                                <Textarea
                                    id="comment-body"
                                    name="body"
                                    rows={4}
                                    required
                                    autoFocus
                                    placeholder="Write your update…"
                                />
                                <InputError message={errors.body} />
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
                                    Post comment
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
