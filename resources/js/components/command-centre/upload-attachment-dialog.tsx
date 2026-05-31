import AttachmentController from '@/actions/App/Http/Controllers/CommandCentre/AttachmentController';
import { Form } from '@inertiajs/react';
import { Paperclip } from 'lucide-react';
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
import { Label } from '@/components/ui/label';

type UploadAttachmentDialogProps = {
    organizationId: number;
    taskId: number;
    trigger?: React.ReactNode;
};

export function UploadAttachmentDialog({
    organizationId,
    taskId,
    trigger,
}: UploadAttachmentDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm" variant="outline">
                        <Paperclip className="size-4" />
                        Upload file
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Upload attachment</DialogTitle>
                    <DialogDescription>
                        Max file size 25 MB.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...AttachmentController.store.form(organizationId)}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="attachable_type"
                                value="task"
                            />
                            <input
                                type="hidden"
                                name="attachable_id"
                                value={taskId}
                            />
                            <div className="grid gap-2">
                                <Label htmlFor="attachment-file">File</Label>
                                <input
                                    id="attachment-file"
                                    name="file"
                                    type="file"
                                    required
                                    className="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm file:font-medium"
                                />
                                <InputError message={errors.file} />
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
                                    Upload
                                </SubmitButton>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
