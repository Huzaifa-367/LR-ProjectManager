import OrganizationRoleController from '@/actions/App/Http/Controllers/OrganizationRoleController';
import { Form, router } from '@inertiajs/react';
import { Lock, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { SubmitButton } from '@/components/submit-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Spinner } from '@/components/ui/spinner';

type OrganizationRoleDetails = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_system: boolean;
    members_count: number;
};

type OrganizationRoleDetailsFormProps = {
    organizationId: number;
    role: OrganizationRoleDetails;
    canUpdate: boolean;
    canDestroy: boolean;
};

export function OrganizationRoleDetailsForm({
    organizationId,
    role,
    canUpdate,
    canDestroy,
}: OrganizationRoleDetailsFormProps) {
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    const destroyRole = (): void => {
        setIsDeleting(true);
        router.delete(
            OrganizationRoleController.destroy.url([
                organizationId,
                role.id,
            ]),
            {
                preserveScroll: true,
                onFinish: () => setIsDeleting(false),
            },
        );
    };

    if (!canUpdate) {
        return (
            <div className="space-y-2 text-sm text-muted-foreground">
                {role.description && <p>{role.description}</p>}
                <p>
                    Slug:{' '}
                    <code className="rounded bg-muted px-1.5 py-0.5 text-xs">
                        {role.slug}
                    </code>
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <Form
                {...OrganizationRoleController.update.form([
                    organizationId,
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
                                <p className="text-sm font-medium">{role.name}</p>
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
                                <Label htmlFor={`role-name-${role.id}`}>
                                    Role name
                                </Label>
                                <Input
                                    id={`role-name-${role.id}`}
                                    name="name"
                                    defaultValue={role.name}
                                    required
                                    disabled={processing}
                                />
                                <InputError message={errors.name} />
                            </div>
                        )}
                        <div className="grid gap-2">
                            <Label htmlFor={`role-description-${role.id}`}>
                                Description
                            </Label>
                            <Textarea
                                id={`role-description-${role.id}`}
                                name="description"
                                defaultValue={role.description ?? ''}
                                rows={3}
                                disabled={processing}
                            />
                            <InputError message={errors.description} />
                        </div>
                        <SubmitButton processing={processing}>
                            Save details
                        </SubmitButton>
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
                            Reassign {role.members_count} member
                            {role.members_count === 1 ? '' : 's'} before
                            deleting this role.
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
