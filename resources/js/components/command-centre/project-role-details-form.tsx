import ProjectRoleController from '@/actions/App/Http/Controllers/CommandCentre/ProjectRoleController';
import { Form, router } from '@inertiajs/react';
import { Lock, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { SubmitButton } from '@/components/submit-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type ProjectRoleDetails = {
    id: number;
    name: string;
    slug: string;
    is_system: boolean;
    members_count: number;
};

type ProjectRoleDetailsFormProps = {
    organizationId: number;
    projectId: number;
    role: ProjectRoleDetails;
    canUpdate: boolean;
    canDestroy: boolean;
};

export function ProjectRoleDetailsForm({
    organizationId,
    projectId,
    role,
    canUpdate,
    canDestroy,
}: ProjectRoleDetailsFormProps) {
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    const destroyRole = (): void => {
        setIsDeleting(true);
        router.delete(
            ProjectRoleController.destroy.url([
                organizationId,
                projectId,
                role.id,
            ]),
            { onFinish: () => setIsDeleting(false) },
        );
    };

    if (!canUpdate) {
        return (
            <p className="text-sm text-muted-foreground">
                Slug:{' '}
                <code className="rounded bg-muted px-1.5 py-0.5 text-xs">
                    {role.slug}
                </code>
            </p>
        );
    }

    return (
        <div className="space-y-4">
            <Form
                {...ProjectRoleController.update.form([
                    organizationId,
                    projectId,
                    role.id,
                ])}
                options={{ preserveScroll: true }}
                className="grid gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        {role.is_system ? (
                            <div className="grid gap-2">
                                <Label>Role name</Label>
                                <p className="text-sm font-medium">
                                    {role.name}
                                </p>
                                <Badge
                                    variant="secondary"
                                    className="w-fit gap-1 text-[10px]"
                                >
                                    <Lock className="size-3" />
                                    System role names cannot be changed
                                </Badge>
                            </div>
                        ) : (
                            <div className="grid gap-2">
                                <Label htmlFor={`project-role-name-${role.id}`}>
                                    Role name
                                </Label>
                                <Input
                                    id={`project-role-name-${role.id}`}
                                    name="name"
                                    defaultValue={role.name}
                                    required
                                    disabled={processing}
                                />
                                <InputError message={errors.name} />
                            </div>
                        )}
                        {!role.is_system && (
                            <SubmitButton processing={processing}>
                                Save name
                            </SubmitButton>
                        )}
                    </>
                )}
            </Form>
            {canDestroy && !role.is_system && (
                <div className="border-t border-border/60 pt-4">
                    {!confirmDelete ? (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            disabled={role.members_count > 0}
                            onClick={() => setConfirmDelete(true)}
                        >
                            <Trash2 className="size-4" />
                            Delete role
                        </Button>
                    ) : (
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-sm text-destructive">
                                Delete this role permanently?
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => setConfirmDelete(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                disabled={isDeleting}
                                onClick={destroyRole}
                            >
                                {isDeleting && <Spinner />}
                                Confirm delete
                            </Button>
                        </div>
                    )}
                    {role.members_count > 0 && (
                        <p className="mt-2 text-xs text-muted-foreground">
                            Reassign {role.members_count} team member
                            {role.members_count === 1 ? '' : 's'} before
                            deleting this role.
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
