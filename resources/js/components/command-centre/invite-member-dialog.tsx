import OrganizationInvitationController from '@/actions/App/Http/Controllers/OrganizationInvitationController';
import { Form } from '@inertiajs/react';
import { MailPlus } from 'lucide-react';
import { useState } from 'react';
import { FormBusyOverlay } from '@/components/command-centre/form-busy-overlay';
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

type InviteMemberDialogProps = {
    organizationId: number;
    roles: Array<{ id: number; name: string; slug: string }>;
    defaultRoleId: number;
};

export function InviteMemberDialog({
    organizationId,
    roles,
    defaultRoleId,
}: InviteMemberDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">
                    <MailPlus className="size-4" />
                    Invite
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Invite by email</DialogTitle>
                    <DialogDescription>
                        Sends an invitation the member can accept from their
                        inbox.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...OrganizationInvitationController.store.form(
                        organizationId,
                    )}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormBusyOverlay
                                visible={processing}
                                title="Sending invitation"
                                description="Delivering the invite email. This may take a moment."
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="invite-email">Email</Label>
                                <Input
                                    id="invite-email"
                                    name="email"
                                    type="email"
                                    required
                                    autoFocus
                                    disabled={processing}
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="invite-role">Role</Label>
                                <select
                                    id="invite-role"
                                    name="organization_role_id"
                                    defaultValue={defaultRoleId}
                                    disabled={processing}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {roles.map((role) => (
                                        <option key={role.id} value={role.id}>
                                            {role.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.organization_role_id}
                                />
                            </div>
                            <InputError message={errors.mail_linkage} />

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={processing}
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <SubmitButton processing={processing}>
                                    Send invitation
                                </SubmitButton>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
