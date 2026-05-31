import OrganizationMemberController from '@/actions/App/Http/Controllers/OrganizationMemberController';
import { Form } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
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

type AddMemberDialogProps = {
    organizationId: number;
    defaultRoleId: number;
};

export function AddMemberDialog({
    organizationId,
    defaultRoleId,
}: AddMemberDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <UserPlus className="size-4" />
                    Add member
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add member</DialogTitle>
                    <DialogDescription>
                        Roster-only entries do not require a login until linked.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...OrganizationMemberController.store.form(organizationId)}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="member-display-name">
                                    Display name
                                </Label>
                                <Input
                                    id="member-display-name"
                                    name="display_name"
                                    required
                                    autoFocus
                                />
                                <InputError message={errors.display_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="member-email">Email</Label>
                                <Input
                                    id="member-email"
                                    name="email"
                                    type="email"
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="member-title">Title</Label>
                                <Input id="member-title" name="title" />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="member-user-id">
                                    User ID (optional)
                                </Label>
                                <Input
                                    id="member-user-id"
                                    name="user_id"
                                    type="number"
                                />
                                <InputError message={errors.user_id} />
                            </div>
                            <input
                                type="hidden"
                                name="organization_role_id"
                                value={defaultRoleId}
                            />
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <SubmitButton processing={processing}>
                                    Add member
                                </SubmitButton>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
