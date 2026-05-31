import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationInvitationController from '@/actions/App/Http/Controllers/OrganizationInvitationController';
import OrganizationMemberController from '@/actions/App/Http/Controllers/OrganizationMemberController';
import { Head, router } from '@inertiajs/react';
import { AddMemberDialog } from '@/components/command-centre/add-member-dialog';
import { CommandCard } from '@/components/command-centre/command-card';
import { EmptyState } from '@/components/command-centre/empty-state';
import { InviteMemberDialog } from '@/components/command-centre/invite-member-dialog';
import { SettingsShell } from '@/components/command-centre/settings-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
import { buildOrganizationSettingsNav } from '@/lib/organization-settings-nav';
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
    const canDisable = canOrg(permissions.org, 'org.members.disable');
    const defaultRoleId =
        roles.find((role) => role.slug === 'member')?.id ?? roles[0]?.id;
    const activeHref = OrganizationMemberController.index.url(
        organization.id,
    );

    return (
        <>
            <Head title={`Members · ${organization.name}`} />
            <SettingsShell
                title="Members"
                description={`People in ${organization.name}.`}
                backHref={OrganizationController.show.url(organization.id)}
                nav={buildOrganizationSettingsNav(
                    organization.id,
                    permissions,
                    activeHref,
                )}
                actions={
                    <div className="flex flex-wrap gap-2">
                        {canStore && defaultRoleId !== undefined && (
                            <AddMemberDialog
                                organizationId={organization.id}
                                defaultRoleId={defaultRoleId}
                            />
                        )}
                        {canInvite && defaultRoleId !== undefined && (
                            <InviteMemberDialog
                                organizationId={organization.id}
                                roles={roles}
                                defaultRoleId={defaultRoleId}
                            />
                        )}
                    </div>
                }
            >
                {invitations.length > 0 && (
                    <CommandCard title="Pending invitations" dot="gold">
                        <ul className="divide-y divide-border/50">
                            {invitations.map((invitation) => (
                                <li
                                    key={invitation.id}
                                    className="tcm-list-row items-center justify-between"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {invitation.email}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {invitation.role_name}
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
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
                                                Resend
                                            </Button>
                                        )}
                                        {canRevokeInvite && (
                                            <Button
                                                variant="ghost"
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
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </CommandCard>
                )}

                <CommandCard
                    title="Roster"
                    dot="blue"
                    badge={
                        <Badge variant="secondary">{members.length}</Badge>
                    }
                >
                    {members.length === 0 ? (
                        <EmptyState>No members yet.</EmptyState>
                    ) : (
                        <ul className="divide-y divide-border/50">
                            {members.map((member) => (
                                <li
                                    key={member.id}
                                    className="tcm-list-row items-center justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {member.display_name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {member.role.name ??
                                                member.role.slug}
                                            {member.title
                                                ? ` · ${member.title}`
                                                : ''}
                                        </p>
                                        {member.email && (
                                            <p className="text-xs text-muted-foreground">
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
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </CommandCard>
            </SettingsShell>
        </>
    );
}
