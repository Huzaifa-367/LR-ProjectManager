import { Head, Link, router } from '@inertiajs/react';
import { Building2, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { create, index, select } from '@/routes/organizations';
import type { OrganizationListItem } from '@/types/organization';

type OrganizationsIndexProps = {
    organizations: OrganizationListItem[];
    pendingInvitations: Array<{
        id: number;
        organization_name: string;
        role_name: string;
        expires_at: string;
        accept_url: string;
    }>;
};

function membershipLabel(membership: OrganizationListItem['membership']): string {
    switch (membership) {
        case 'owner':
            return 'Owner';
        case 'invited':
            return 'Invited';
        default:
            return 'Member';
    }
}

export default function OrganizationsIndex({
    organizations,
    pendingInvitations,
}: OrganizationsIndexProps) {
    const hasOrganizations = organizations.length > 0;

    return (
        <>
            <Head title="Organizations" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Organizations"
                        description="Choose a workspace or create a new organization."
                    />
                    <Button asChild>
                        <Link href={create.url()}>
                            <Plus className="size-4" />
                            Create organization
                        </Link>
                    </Button>
                </div>

                {!hasOrganizations ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>No organizations yet</CardTitle>
                            <CardDescription>
                                Create your first organization to open the
                                command centre.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild>
                                <Link href={create.url()}>
                                    Create organization
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-3">
                        {organizations.map((organization) => (
                            <Card key={organization.id}>
                                <CardContent className="flex items-center justify-between gap-4 p-4">
                                    <div className="flex min-w-0 items-center gap-3">
                                        <div className="flex size-10 shrink-0 items-center justify-center rounded-md bg-muted">
                                            <Building2 className="size-5 text-muted-foreground" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {organization.name}
                                            </p>
                                            <div className="mt-1 flex flex-wrap gap-2">
                                                <Badge variant="secondary">
                                                    {membershipLabel(
                                                        organization.membership,
                                                    )}
                                                </Badge>
                                                {organization.is_primary_org && (
                                                    <Badge variant="outline">
                                                        Primary
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    {organization.member_status ===
                                    'active' ? (
                                        <Button
                                            onClick={() => {
                                                router.post(
                                                    select.url(),
                                                    {
                                                        organization_id:
                                                            organization.id,
                                                    },
                                                );
                                            }}
                                        >
                                            Open
                                        </Button>
                                    ) : (
                                        <Button variant="outline" disabled>
                                            Pending invite
                                        </Button>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {pendingInvitations.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Pending invitations</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm text-muted-foreground">
                            {pendingInvitations.map((invitation) => (
                                <div
                                    key={invitation.id}
                                    className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <p>
                                        {invitation.organization_name} ·{' '}
                                        {invitation.role_name}
                                    </p>
                                    <Button
                                        size="sm"
                                        onClick={() => {
                                            router.post(invitation.accept_url);
                                        }}
                                    >
                                        Accept
                                    </Button>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <p className="text-sm text-muted-foreground">
                    <Link
                        href={index.url()}
                        className="underline underline-offset-4"
                    >
                        Refresh this list
                    </Link>
                </p>
            </div>
        </>
    );
}
