import ProjectMemberController from '@/actions/App/Http/Controllers/CommandCentre/ProjectMemberController';
import { Form } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { useMemo, useState } from 'react';
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

type ProjectRoleOption = {
    id: number;
    name: string;
    slug: string;
};

type TeamMemberRow = {
    id: number;
    display_name: string | null;
    role: {
        id: number | undefined;
        name: string | undefined;
        slug: string | undefined;
    };
};

type EditProjectMemberRoleDialogProps = {
    organizationId: number;
    projectId: number;
    member: TeamMemberRow;
    roles: ProjectRoleOption[];
    trigger?: React.ReactNode;
};

export function EditProjectMemberRoleDialog({
    organizationId,
    projectId,
    member,
    roles,
    trigger,
}: EditProjectMemberRoleDialogProps) {
    const [open, setOpen] = useState(false);
    const roleId = member.role.id ?? roles[0]?.id ?? '';

    const selectableRoles = useMemo(() => roles, [roles]);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm" variant="outline">
                        <Pencil className="size-4" />
                        Change role
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Update project role</DialogTitle>
                    <DialogDescription>
                        Change the project role for{' '}
                        {member.display_name ?? 'this member'}.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...ProjectMemberController.update.form([
                        organizationId,
                        projectId,
                        member.id,
                    ])}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`project-role-${member.id}`}
                                >
                                    Project role
                                </Label>
                                <select
                                    id={`project-role-${member.id}`}
                                    name="project_role_id"
                                    defaultValue={roleId}
                                    required
                                    disabled={processing}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {selectableRoles.map((role) => (
                                        <option key={role.id} value={role.id}>
                                            {role.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.project_role_id} />
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
                                <SubmitButton processing={processing}>
                                    Save role
                                </SubmitButton>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
