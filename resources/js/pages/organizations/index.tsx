import { Head, Link, router } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { useState } from 'react';
import { CommandCard } from '@/components/command-centre/command-card';
import { CreateOrganizationDialog } from '@/components/command-centre/create-organization-dialog';
import { EmptyState } from '@/components/command-centre/empty-state';
import { PageShell } from '@/components/command-centre/page-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { index, select } from '@/routes/organizations';
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
    const [pendingAction, setPendingAction] = useState<string | null>(null);

    const finishAction = (): void => setPendingAction(null);
    const isPending = (key: string): boolean => pendingAction === key;

    return (
        <>
            <Head title="Organizations" />
            <PageShell
                title="Organizations"
                subtitle="Choose a workspace or create a new one."
                stats={[
                    {
                        label: 'Workspaces',
                        value: organizations.length,
                    },
                ]}
                actions={<CreateOrganizationDialog />}
            >
                {!hasOrganizations ? (
                    <CommandCard title="Get started" dot="crimson">
                        <EmptyState>
                            Create your first organization to open the command
                            centre.
                        </EmptyState>
                        <div className="flex justify-center pb-2">
                            <CreateOrganizationDialog />
                        </div>
                    </CommandCard>
                ) : (
                    <CommandCard title="Your workspaces" dot="blue">
                        <ul className="divide-y divide-border/50">
                            {organizations.map((organization) => (
                                <li
                                    key={organization.id}
                                    className="tcm-list-row items-center"
                                >
                                    <div className="flex min-w-0 flex-1 items-center gap-3">
                                        <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted/60">
                                            <Building2 className="size-4 text-muted-foreground" />
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
                                    {organization.member_status === 'active' ? (
                                        <Button
                                            size="sm"
                                            disabled={isPending(
                                                `open-${organization.id}`,
                                            )}
                                            onClick={() => {
                                                setPendingAction(
                                                    `open-${organization.id}`,
                                                );
                                                router.post(
                                                    select.url(),
                                                    {
                                                        organization_id:
                                                            organization.id,
                                                    },
                                                    { onFinish: finishAction },
                                                );
                                            }}
                                        >
                                            {isPending(
                                                `open-${organization.id}`,
                                            ) && <Spinner />}
                                            Open
                                        </Button>
                                    ) : (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled
                                        >
                                            Pending
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </CommandCard>
                )}

                {pendingInvitations.length > 0 && (
                    <CommandCard title="Pending invitations" dot="gold">
                        <ul className="divide-y divide-border/50">
                            {pendingInvitations.map((invitation) => (
                                <li
                                    key={invitation.id}
                                    className="tcm-list-row items-center justify-between"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {invitation.organization_name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {invitation.role_name}
                                        </p>
                                    </div>
                                    <Button
                                        size="sm"
                                        disabled={isPending(
                                            `accept-${invitation.id}`,
                                        )}
                                        onClick={() => {
                                            setPendingAction(
                                                `accept-${invitation.id}`,
                                            );
                                            router.post(
                                                invitation.accept_url,
                                                {},
                                                { onFinish: finishAction },
                                            );
                                        }}
                                    >
                                        {isPending(
                                            `accept-${invitation.id}`,
                                        ) && <Spinner />}
                                        Accept
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </CommandCard>
                )}

                <p className="text-center text-xs text-muted-foreground">
                    <Link
                        href={index.url()}
                        className="underline-offset-4 hover:underline"
                    >
                        Refresh list
                    </Link>
                </p>
            </PageShell>
        </>
    );
}
