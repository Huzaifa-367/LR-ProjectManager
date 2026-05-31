import MemberNoteController from '@/actions/App/Http/Controllers/CommandCentre/MemberNoteController';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type CreateNoteDialogProps = {
    organizationId: number;
};

export function CreateNoteDialog({ organizationId }: CreateNoteDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="ghost" className="h-8 px-2 text-xs">
                    <Plus className="size-3.5" />
                    Add note
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add note</DialogTitle>
                    <DialogDescription>
                        Personal notes are only visible to you.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...MemberNoteController.store.form(organizationId)}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="note-body">Note</Label>
                                <Textarea
                                    id="note-body"
                                    name="body"
                                    required
                                    autoFocus
                                    rows={4}
                                    placeholder="Capture a thought…"
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
                                    Save note
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
