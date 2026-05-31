import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
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

type CreateOrganizationDialogProps = {
    trigger?: React.ReactNode;
};

export function CreateOrganizationDialog({
    trigger,
}: CreateOrganizationDialogProps) {
    const [open, setOpen] = useState(false);
    const defaultTimezone =
        Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button>
                        <Plus className="size-4" />
                        Create organization
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Create organization</DialogTitle>
                    <DialogDescription>
                        Set up a new workspace for your command centre.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...OrganizationController.store.form()}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="create-org-name">Name</Label>
                                <Input
                                    id="create-org-name"
                                    name="name"
                                    required
                                    autoFocus
                                    placeholder="TCM Group"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="create-org-timezone">
                                    Timezone
                                </Label>
                                <Input
                                    id="create-org-timezone"
                                    name="settings[timezone]"
                                    defaultValue={defaultTimezone}
                                />
                                <InputError
                                    message={errors['settings.timezone']}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="create-org-focus-cap">
                                    Daily focus cap
                                </Label>
                                <Input
                                    id="create-org-focus-cap"
                                    name="settings[focus_cap]"
                                    type="number"
                                    min={1}
                                    max={10}
                                    defaultValue={10}
                                />
                                <InputError
                                    message={errors['settings.focus_cap']}
                                />
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
                                    Create organization
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
