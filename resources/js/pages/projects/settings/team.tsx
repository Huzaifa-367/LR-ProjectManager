import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import ProjectMemberController from '@/actions/App/Http/Controllers/CommandCentre/ProjectMemberController';
import { Head, Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { AddProjectMemberDialog } from '@/components/command-centre/add-project-member-dialog';
import { CommandCard } from '@/components/command-centre/command-card';
import { EditProjectMemberRoleDialog } from '@/components/command-centre/edit-project-member-role-dialog';
import { EmptyState } from '@/components/command-centre/empty-state';
import { PageShell } from '@/components/command-centre/page-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { canProject } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type TeamMember = {
    id: number;
    organization_member_id: number;
    display_name: string | null;
    role: {
        id: number | undefined;
        name: string | undefined;
        slug: string | undefined;
    };
    joined_at: string | null;
};

type AvailableMember = {
    id: number;
    display_name: string;
    email: string | null;
    title: string | null;
};

type ProjectTeamProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    project: { id: number; name: string };
    team: TeamMember[];
    roles: Array<{ id: number; name: string; slug: string }>;
    availableMembers: AvailableMember[];
    permissions: CommandCentrePermissions;
};

export default function ProjectTeamSettings({
    organization,
    project,
    team,
    roles,
    availableMembers,
    permissions,
}: ProjectTeamProps) {
    const canStore = canProject(
        permissions,
        project.id,
        'project.members.store',
    );
    const canUpdate = canProject(
        permissions,
        project.id,
        'project.members.update',
    );
    const canDestroy = canProject(
        permissions,
        project.id,
        'project.members.destroy',
    );
    const [pendingRemoveId, setPendingRemoveId] = useState<number | null>(null);

    const removeMember = (projectMemberId: number): void => {
        if (
            !window.confirm(
                'Remove this member from the project team? They will lose project-scoped access.',
            )
        ) {
            return;
        }

        setPendingRemoveId(projectMemberId);
        router.delete(
            ProjectMemberController.destroy.url([
                organization.id,
                project.id,
                projectMemberId,
            ]),
            { preserveScroll: true, onFinish: () => setPendingRemoveId(null) },
        );
    };

    return (
        <>
            <Head title={`Team · ${project.name}`} />
            <PageShell
                title={`Team · ${project.name}`}
                subtitle={organization.name}
                stats={[{ label: 'Members', value: team.length }]}
                actions={
                    <div className="flex flex-col items-end gap-2">
                        <div className="flex flex-wrap gap-2">
                        {canStore && (
                            <AddProjectMemberDialog
                                organizationId={organization.id}
                                projectId={project.id}
                                availableMembers={availableMembers}
                                roles={roles}
                            />
                        )}
                        <Button asChild variant="outline" size="sm">
                            <Link
                                href={ProjectController.show.url([
                                    organization.id,
                                    project.id,
                                ])}
                            >
                                Back to project
                            </Link>
                        </Button>
                        </div>
                        {!canStore && (
                            <p className="max-w-sm text-right text-xs text-muted-foreground">
                                You can view the team but cannot add or change
                                members with your current project role.
                            </p>
                        )}
                    </div>
                }
            >
                <CommandCard
                    title="Roster"
                    description="People on this project and their project roles (lead, contributor, viewer, or owner)."
                    dot="blue"
                >
                    {team.length === 0 ? (
                        <EmptyState>
                            No team members yet.
                            {canStore &&
                                availableMembers.length > 0 &&
                                ' Use Add to team to assign organization members.'}
                            {canStore &&
                                availableMembers.length === 0 &&
                                ' Add people under Organization settings → Members first.'}
                        </EmptyState>
                    ) : (
                        <ul className="divide-y divide-border/50">
                            {team.map((member) => {
                                const isRemoving =
                                    pendingRemoveId === member.id;

                                return (
                                    <li
                                        key={member.id}
                                        className="tcm-list-row items-center justify-between gap-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {member.display_name ??
                                                    'Unknown member'}
                                            </p>
                                            <div className="mt-1 flex flex-wrap items-center gap-2">
                                                <Badge variant="outline">
                                                    {member.role.name ??
                                                        member.role.slug?.replace(
                                                            /_/g,
                                                            ' ',
                                                        ) ??
                                                        'No role'}
                                                </Badge>
                                                {member.role.slug ===
                                                    'project_owner' && (
                                                    <span className="text-xs text-muted-foreground">
                                                        Project creator
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            {canUpdate && (
                                                <EditProjectMemberRoleDialog
                                                    organizationId={
                                                        organization.id
                                                    }
                                                    projectId={project.id}
                                                    member={member}
                                                    roles={roles}
                                                />
                                            )}
                                            {canDestroy &&
                                                member.role.slug !==
                                                    'project_owner' && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={isRemoving}
                                                        onClick={() =>
                                                            removeMember(
                                                                member.id,
                                                            )
                                                        }
                                                    >
                                                        {isRemoving ? (
                                                            <Spinner />
                                                        ) : (
                                                            <Trash2 className="size-4" />
                                                        )}
                                                        Remove
                                                    </Button>
                                                )}
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </CommandCard>
            </PageShell>
        </>
    );
}
