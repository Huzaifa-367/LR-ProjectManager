import OrganizationMemberController from '@/actions/App/Http/Controllers/OrganizationMemberController';
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

type MemberRole = {
    id: number;
    name: string;
    slug: string;
};

type EditableMember = {
    id: number;
    display_name: string;
    email: string | null;
    title: string | null;
    role: {
        id: number | undefined;
    };
};

type EditMemberDialogProps = {
    organizationId: number;
    member: EditableMember;
    roles: MemberRole[];
    trigger?: React.ReactNode;
};

export function EditMemberDialog({
    organizationId,
    member,
    roles,
    trigger,
}: EditMemberDialogProps) {
    const [open, setOpen] = useState(false);
    const roleId = member.role.id ?? roles[0]?.id ?? '';

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm" variant="ghost">
                        <Pencil className="size-4" />
                        <span className="sr-only">Edit member</span>
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit member</DialogTitle>
                    <DialogDescription>
                        Update roster details and organization role for{' '}
                        {member.display_name}.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...OrganizationMemberController.update.form([
                        organizationId,
                        member.id,
                    ])}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`edit-member-name-${member.id}`}>
                                    Display name
                                </Label>
                                <Input
                                    id={`edit-member-name-${member.id}`}
                                    name="display_name"
                                    defaultValue={member.display_name}
                                    required
                                    autoFocus
                                    disabled={processing}
                                />
                                <InputError message={errors.display_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`edit-member-email-${member.id}`}>
                                    Email
                                </Label>
                                <Input
                                    id={`edit-member-email-${member.id}`}
                                    name="email"
                                    type="email"
                                    defaultValue={member.email ?? ''}
                                    disabled={processing}
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`edit-member-title-${member.id}`}>
                                    Title
                                </Label>
                                <Input
                                    id={`edit-member-title-${member.id}`}
                                    name="title"
                                    defaultValue={member.title ?? ''}
                                    disabled={processing}
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`edit-member-role-${member.id}`}>
                                    Role
                                </Label>
                                <select
                                    id={`edit-member-role-${member.id}`}
                                    name="organization_role_id"
                                    defaultValue={roleId}
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
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={processing}
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <SubmitButton
                                    processing={processing}
                                    data-test={`save-member-${member.id}`}
                                >
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
