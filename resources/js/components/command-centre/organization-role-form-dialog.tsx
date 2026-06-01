import OrganizationRoleController from '@/actions/App/Http/Controllers/OrganizationRoleController';
import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PermissionMatrix from '@/components/permission-matrix';
import { SubmitButton } from '@/components/submit-button';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogBody,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { PermissionGroup } from '@/types/permission';

type OrganizationRoleFormDialogProps = {
    organizationId: number;
    permissionGroups: PermissionGroup[];
};

export function OrganizationRoleFormDialog({
    organizationId,
    permissionGroups,
}: OrganizationRoleFormDialogProps) {
    const [open, setOpen] = useState(false);
    const [permissions, setPermissions] = useState<string[]>([]);

    const groups = permissionGroups.map((group) => ({
        ...group,
        description: group.description ?? '',
    }));

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">
                    <Plus className="size-4" />
                    Create role
                </Button>
            </DialogTrigger>
            <DialogContent className="flex max-h-[min(90vh,42rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-2xl">
                {open && (
                    <Form
                        {...OrganizationRoleController.store.form(
                            organizationId,
                        )}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setOpen(false)}
                        className="flex min-h-0 flex-1 flex-col"
                    >
                        {({ processing, errors }) => (
                            <>
                                <DialogHeader className="shrink-0 space-y-1 border-b px-6 py-4">
                                    <DialogTitle className="text-xl font-semibold">
                                        Create organization role
                                    </DialogTitle>
                                    <DialogDescription className="text-sm">
                                        Custom roles can be assigned to members
                                        and tuned with org-level permissions.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogBody className="space-y-6 py-5">
                                    <div className="grid gap-2">
                                        <Label htmlFor="org-role-name">
                                            Role name
                                        </Label>
                                        <Input
                                            id="org-role-name"
                                            name="name"
                                            required
                                            autoFocus
                                            disabled={processing}
                                            placeholder="e.g. Delivery lead"
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="org-role-description">
                                            Description
                                        </Label>
                                        <Textarea
                                            id="org-role-description"
                                            name="description"
                                            rows={3}
                                            disabled={processing}
                                            placeholder="What is this role responsible for?"
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <p className="text-sm font-medium">
                                            Permissions
                                        </p>
                                        <PermissionMatrix
                                            groups={groups}
                                            value={permissions}
                                            onChange={setPermissions}
                                        />
                                        <InputError
                                            message={
                                                errors.permissions ??
                                                errors['permissions.0']
                                            }
                                        />
                                    </div>
                                </DialogBody>
                                <DialogFooter className="flex flex-row items-center justify-end gap-2 border-t bg-muted/30 px-6 py-3">
                                    <DialogClose asChild>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            disabled={processing}
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <SubmitButton processing={processing}>
                                        Create role
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
