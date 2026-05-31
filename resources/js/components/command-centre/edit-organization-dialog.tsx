import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import { Form } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
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

type EditOrganizationDialogProps = {
    organization: {
        id: number;
        name: string;
        settings: {
            timezone: string;
            focus_cap: number;
            ai_enabled: boolean;
        };
    };
    trigger?: React.ReactNode;
};

export function EditOrganizationDialog({
    organization,
    trigger,
}: EditOrganizationDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button variant="outline" size="sm">
                        <Pencil className="size-4" />
                        Edit profile
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Organization profile</DialogTitle>
                    <DialogDescription>
                        Update name and workspace defaults.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...OrganizationController.update.form(organization.id)}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-org-name">Name</Label>
                                <Input
                                    id="edit-org-name"
                                    name="name"
                                    defaultValue={organization.name}
                                    required
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-org-timezone">
                                    Timezone
                                </Label>
                                <Input
                                    id="edit-org-timezone"
                                    name="settings[timezone]"
                                    defaultValue={
                                        organization.settings.timezone
                                    }
                                />
                                <InputError
                                    message={errors['settings.timezone']}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-org-focus-cap">
                                    Daily focus cap
                                </Label>
                                <Input
                                    id="edit-org-focus-cap"
                                    name="settings[focus_cap]"
                                    type="number"
                                    min={1}
                                    max={10}
                                    defaultValue={
                                        organization.settings.focus_cap
                                    }
                                />
                                <InputError
                                    message={errors['settings.focus_cap']}
                                />
                            </div>
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="settings[ai_enabled]"
                                    value="1"
                                    defaultChecked={
                                        organization.settings.ai_enabled
                                    }
                                />
                                AI features enabled
                            </label>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <SubmitButton processing={processing}>
                                    Save changes
                                </SubmitButton>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
