import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationInvitationController from '@/actions/App/Http/Controllers/OrganizationInvitationController';
import OrganizationMemberController from '@/actions/App/Http/Controllers/OrganizationMemberController';
import { Form, Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { canOrg } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type MemberRow = {
    id: number;
    display_name: string;
    email: string | null;
    title: string | null;
    status: string;
    user_id: number | null;
    role: {
        id: number | undefined;
        name: string | undefined;
        slug: string | undefined;
    };
};

type InvitationRow = {
    id: number;
    email: string;
    role_name: string;
    expires_at: string;
};

type OrganizationMembersProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    members: MemberRow[];
    invitations: InvitationRow[];
    roles: Array<{ id: number; name: string; slug: string }>;
    permissions: CommandCentrePermissions;
};

export default function OrganizationMembersSettings({
    organization,
    members,
    invitations,
    roles,
    permissions,
}: OrganizationMembersProps) {
    const canStore = canOrg(permissions.org, 'org.members.store');
    const canInvite = canOrg(permissions.org, 'org.invitations.store');
    const canRevokeInvite = canOrg(permissions.org, 'org.invitations.destroy');
    const canResendInvite = canOrg(permissions.org, 'org.invitations.resend');
    const canUpdate = canOrg(permissions.org, 'org.members.update');
    const canDisable = canOrg(permissions.org, 'org.members.disable');
    const defaultRoleId =
        roles.find((role) => role.slug === 'member')?.id ?? roles[0]?.id;

    return (
        <>
            <Head title={`Members · ${organization.name}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Members"
                        description={`People in ${organization.name}.`}
                    />
                    <Link
                        href={OrganizationController.show.url(organization.id)}
                        className="text-sm underline underline-offset-4"
                    >
                        Settings
                    </Link>
                </div>

                {canStore && defaultRoleId !== undefined && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Add member</CardTitle>
                            <CardDescription>
                                Roster-only entries do not require a login. Link
                                an existing user with user ID when ready.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...OrganizationMemberController.store.form(
                                    organization.id,
                                )}
                                className="grid gap-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="display_name">
                                                Display name
                                            </Label>
                                            <Input
                                                id="display_name"
                                                name="display_name"
                                                required
                                            />
                                            <InputError
                                                message={errors.display_name}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="email">Email</Label>
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                            />
                                            <InputError message={errors.email} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="title">Title</Label>
                                            <Input id="title" name="title" />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="user_id">
                                                User ID (optional)
                                            </Label>
                                            <Input
                                                id="user_id"
                                                name="user_id"
                                                type="number"
                                            />
                                            <InputError
                                                message={errors.user_id}
                                            />
                                        </div>
                                        <input
                                            type="hidden"
                                            name="organization_role_id"
                                            value={defaultRoleId}
                                        />
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <Plus className="size-4" />
                                            Add member
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {canInvite && defaultRoleId !== undefined && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Invite by email</CardTitle>
                            <CardDescription>
                                Creates a pending invitation with an accept link
                                (email delivery comes in a later milestone).
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...OrganizationInvitationController.store.form(
                                    organization.id,
                                )}
                                className="grid gap-4 sm:grid-cols-2"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="invite_email">
                                                Email
                                            </Label>
                                            <Input
                                                id="invite_email"
                                                name="email"
                                                type="email"
                                                required
                                            />
                                            <InputError message={errors.email} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="invite_role">
                                                Role
                                            </Label>
                                            <select
                                                id="invite_role"
                                                name="organization_role_id"
                                                defaultValue={defaultRoleId}
                                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                            >
                                                {roles.map((role) => (
                                                    <option
                                                        key={role.id}
                                                        value={role.id}
                                                    >
                                                        {role.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="flex items-end">
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Send invitation
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {invitations.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Pending invitations</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {invitations.map((invitation) => (
                                <div
                                    key={invitation.id}
                                    className="flex flex-col gap-2 border-b pb-3 last:border-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {invitation.email}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {invitation.role_name}
                                        </p>
                                    </div>
                                    {canRevokeInvite && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                router.delete(
                                                    OrganizationInvitationController.destroy.url(
                                                        [
                                                            organization.id,
                                                            invitation.id,
                                                        ],
                                                    ),
                                                );
                                            }}
                                        >
                                            Revoke
                                        </Button>
                                    )}
                                    {canResendInvite && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                router.post(
                                                    OrganizationInvitationController.resend.url(
                                                        [
                                                            organization.id,
                                                            invitation.id,
                                                        ],
                                                    ),
                                                );
                                            }}
                                        >
                                            Resend email
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Roster</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {members.map((member) => (
                            <div
                                key={member.id}
                                className="flex flex-col gap-2 border-b pb-4 last:border-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p className="font-medium">
                                        {member.display_name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {member.role.name ?? member.role.slug}
                                        {member.title
                                            ? ` · ${member.title}`
                                            : ''}
                                    </p>
                                    {member.email && (
                                        <p className="text-sm text-muted-foreground">
                                            {member.email}
                                        </p>
                                    )}
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant={
                                            member.status === 'active'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {member.status}
                                    </Badge>
                                    {canDisable &&
                                        member.status === 'active' && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    router.patch(
                                                        OrganizationMemberController.disable.url(
                                                            [
                                                                organization.id,
                                                                member.id,
                                                            ],
                                                        ),
                                                    );
                                                }}
                                            >
                                                Disable
                                            </Button>
                                        )}
                                    {canUpdate && (
                                        <span className="text-xs text-muted-foreground">
                                            {member.user_id
                                                ? `User #${member.user_id}`
                                                : 'Roster only'}
                                        </span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
