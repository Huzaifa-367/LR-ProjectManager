import { Form } from '@inertiajs/react';
import { useState } from 'react';
import UserRoleController from '@/actions/App/Http/Controllers/Settings/UserRoleController';
import InputError from '@/components/input-error';
import { SubmitButton } from '@/components/submit-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogBody,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { cn } from '@/lib/utils';
import type { RoleSummary } from '@/types/permission';

type CreateUserDialogProps = {
    roles: RoleSummary[];
    trigger: React.ReactNode;
};

export default function CreateUserDialog({
    roles,
    trigger,
}: CreateUserDialogProps) {
    const [open, setOpen] = useState<boolean>(false);
    const [selectedRole, setSelectedRole] = useState<string>(
        roles[0]?.name ?? '',
    );

    const reset = (): void => setSelectedRole(roles[0]?.name ?? '');

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (!next) {
                    reset();
                }
            }}
        >
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="flex max-h-[min(90vh,40rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-lg">
                <DialogHeader className="shrink-0 space-y-2 px-6 pt-6 pb-2">
                    <DialogTitle>Add user</DialogTitle>
                    <DialogDescription>
                        Create a login for someone on the platform. They will
                        receive an email with a temporary password.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...UserRoleController.store.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="flex min-h-0 flex-1 flex-col"
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="role"
                                value={selectedRole}
                            />

                            <DialogBody className="space-y-4 py-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="create-user-name">
                                        Full name
                                    </Label>
                                    <Input
                                        id="create-user-name"
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Jane Doe"
                                        data-test="create-user-name"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="create-user-email">
                                        Email address
                                    </Label>
                                    <Input
                                        id="create-user-email"
                                        type="email"
                                        name="email"
                                        required
                                        autoComplete="email"
                                        placeholder="jane@example.com"
                                        data-test="create-user-email"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                {roles.length === 0 ? (
                                    <p className="rounded-md border px-2 py-6 text-center text-sm text-muted-foreground">
                                        No roles available. Create a role first.
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        <Label>Role</Label>
                                        <RadioGroup
                                            value={selectedRole}
                                            onValueChange={setSelectedRole}
                                            aria-label="Select a role"
                                            className="gap-2 rounded-md border p-2"
                                        >
                                            {roles.map((role) => (
                                                <RoleRadioOption
                                                    key={role.id}
                                                    id={`create-user-role-${role.id}`}
                                                    value={role.name}
                                                    selected={
                                                        selectedRole ===
                                                        role.name
                                                    }
                                                    title={role.name}
                                                    description={
                                                        role.description
                                                    }
                                                    badge={role.name}
                                                />
                                            ))}
                                        </RadioGroup>
                                    </div>
                                )}

                                <InputError message={errors.role} />
                            </DialogBody>

                            <DialogFooter className="shrink-0 gap-2 border-t bg-muted/30 px-6 py-4 sm:justify-end">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => {
                                        reset();
                                        setOpen(false);
                                    }}
                                >
                                    Cancel
                                </Button>
                                <SubmitButton
                                    processing={processing}
                                    disabled={
                                        selectedRole === '' ||
                                        roles.length === 0
                                    }
                                    data-test="create-user-submit"
                                >
                                    Create user
                                </SubmitButton>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

type RoleRadioOptionProps = {
    id: string;
    value: string;
    selected: boolean;
    title: string;
    description?: string | null;
    badge?: string;
};

function RoleRadioOption({
    id,
    value,
    selected,
    title,
    description,
    badge,
}: RoleRadioOptionProps) {
    return (
        <Label
            htmlFor={id}
            className={cn(
                'flex cursor-pointer items-start gap-3 rounded-md border border-transparent p-2 font-normal transition-colors hover:bg-muted/40',
                selected && 'border-primary/40 bg-primary/5',
            )}
        >
            <RadioGroupItem id={id} value={value} className="mt-1" />

            <div className="flex-1 space-y-0.5">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-medium capitalize">
                        {title}
                    </span>
                    {badge && (
                        <Badge
                            variant="outline"
                            className="font-mono text-[10px] uppercase"
                        >
                            {badge}
                        </Badge>
                    )}
                </div>
                {description && (
                    <p className="text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
        </Label>
    );
}
