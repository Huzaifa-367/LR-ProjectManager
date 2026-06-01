import OrganizationMemberController from '@/actions/App/Http/Controllers/OrganizationMemberController';
import ProjectMemberController from '@/actions/App/Http/Controllers/CommandCentre/ProjectMemberController';
import { Form, Link } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
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
import { defaultProjectRoleSlugForNewMember } from '@/lib/project-role-options';

type AvailableMember = {
    id: number;
    display_name: string;
    email: string | null;
    title: string | null;
};

type ProjectRoleOption = {
    id: number;
    name: string;
    slug: string;
};

type AddProjectMemberDialogProps = {
    organizationId: number;
    projectId: number;
    availableMembers: AvailableMember[];
    roles: ProjectRoleOption[];
};

export function AddProjectMemberDialog({
    organizationId,
    projectId,
    availableMembers,
    roles,
}: AddProjectMemberDialogProps) {
    const [open, setOpen] = useState(false);

    const defaultRoleId = useMemo(() => {
        const contributor = roles.find(
            (role) => role.slug === defaultProjectRoleSlugForNewMember(),
        );

        return contributor?.id ?? roles[0]?.id;
    }, [roles]);

    const assignableRoles = useMemo(
        () =>
            roles.filter((role) => role.slug !== 'project_owner'),
        [roles],
    );

    const hasAvailableMembers = availableMembers.length > 0;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">
                    <UserPlus className="size-4" />
                    Add to team
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add project team member</DialogTitle>
                    <DialogDescription>
                        {hasAvailableMembers
                            ? 'Choose someone from your organization roster and assign their project role.'
                            : 'Everyone in the organization is already on this project team. Add people under Organization settings → Members first.'}
                    </DialogDescription>
                </DialogHeader>
                {!hasAvailableMembers ? (
                    <div className="space-y-4 px-6 pb-6">
                        <p className="text-sm text-muted-foreground">
                            Everyone in this organization is already on the project
                            team. Add more people to the organization first, then
                            assign them here.
                        </p>
                        <DialogFooter className="px-0 pb-0 sm:justify-start">
                            <Button asChild>
                                <Link
                                    href={OrganizationMemberController.index.url(
                                        organizationId,
                                    )}
                                >
                                    Organization members
                                </Link>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpen(false)}
                            >
                                Close
                            </Button>
                        </DialogFooter>
                    </div>
                ) : (
                <Form
                    {...ProjectMemberController.store.form([
                        organizationId,
                        projectId,
                    ])}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="project-member-select">
                                    Member
                                </Label>
                                <select
                                    id="project-member-select"
                                    name="organization_member_id"
                                    required
                                    disabled={processing}
                                    defaultValue={
                                        availableMembers[0]?.id ?? ''
                                    }
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {availableMembers.map((member) => (
                                        <option
                                            key={member.id}
                                            value={member.id}
                                        >
                                            {member.display_name}
                                            {member.title
                                                ? ` · ${member.title}`
                                                : ''}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.organization_member_id}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="project-member-role">
                                    Project role
                                </Label>
                                <select
                                    id="project-member-role"
                                    name="project_role_id"
                                    required
                                    disabled={processing}
                                    defaultValue={defaultRoleId}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {assignableRoles.map((role) => (
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
                                    Add member
                                </SubmitButton>
                            </DialogFooter>
                        </>
                    )}
                </Form>
                )}
            </DialogContent>
        </Dialog>
    );
}
