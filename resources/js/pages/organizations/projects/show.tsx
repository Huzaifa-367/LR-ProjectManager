import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import ProjectMemberController from '@/actions/App/Http/Controllers/CommandCentre/ProjectMemberController';
import ProjectRoleController from '@/actions/App/Http/Controllers/CommandCentre/ProjectRoleController';
import { Head, Link } from '@inertiajs/react';
import { ExpandableText } from '@/components/command-centre/expandable-text';
import { CommandCard } from '@/components/command-centre/command-card';
import { CreateTaskDialog } from '@/components/command-centre/create-task-dialog';
import { PageShell } from '@/components/command-centre/page-shell';
import { ProjectKanban } from '@/components/command-centre/project-kanban';
import { StatusPill } from '@/components/command-centre/status-pill';
import { TaskKindLegend } from '@/components/command-centre/task-kind-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { canOrg, canProject } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';
import { cn } from '@/lib/utils';

type ProjectDetail = {
    id: number;
    name: string;
    objective: string | null;
    progress_percent: number;
    next_action: string | null;
    health: string;
};

type ProjectTask = {
    id: number;
    kind: string;
    title: string;
    status: string;
    is_done: boolean;
    priority: string | null;
    assignees: Array<{ id: number; display_name: string | null }>;
};

type TeamMember = {
    id: number;
    organization_member_id: number;
    display_name: string | null;
    role_name: string | null | undefined;
};

type ProjectShowProps = {
    organization: OrganizationSummary;
    project: ProjectDetail;
    tasks: ProjectTask[];
    team: TeamMember[];
    taskSummary: { open: number; done: number };
    permissions: CommandCentrePermissions;
};

const healthDotClass: Record<string, string> = {
    active: 'bg-primary',
    progressing: 'bg-blue-500',
    steady: 'bg-emerald-500',
    at_risk: 'bg-amber-500',
};

export default function ProjectShow({
    organization,
    project,
    tasks,
    team,
    taskSummary,
    permissions,
}: ProjectShowProps) {
    const canManageTeam = canProject(
        permissions,
        project.id,
        'project.members.index',
    );
    const canManageRoles = canProject(
        permissions,
        project.id,
        'project.roles.index',
    );
    const canCreateTask = canOrg(permissions.org, 'org.tasks.store');

    return (
        <>
            <Head title={project.name} />
            <PageShell
                title={project.name}
                breadcrumbs={[
                    {
                        title: organization.name,
                        href: `/organizations/${organization.id}/command-centre`,
                    },
                    {
                        title: 'Projects',
                        href: ProjectController.index.url(organization.id),
                    },
                    {
                        title: project.name,
                        href: ProjectController.show.url([
                            organization.id,
                            project.id,
                        ]),
                    },
                ]}
                stats={[
                    {
                        label: 'Progress',
                        value: `${project.progress_percent}%`,
                        tone: 'accent',
                    },
                    { label: 'Open', value: taskSummary.open },
                    {
                        label: 'Done',
                        value: taskSummary.done,
                        tone: 'success',
                    },
                ]}
                actions={
                    <div className="flex flex-wrap gap-2">
                        {canCreateTask && (
                            <CreateTaskDialog
                                organizationId={organization.id}
                                projects={[
                                    { id: project.id, name: project.name },
                                ]}
                                defaultProjectId={project.id}
                                teamMembers={team.map((member) => ({
                                    id: member.organization_member_id,
                                    display_name: member.display_name,
                                    role_name: member.role_name,
                                }))}
                            />
                        )}
                        <Button asChild variant="outline" size="sm">
                            <Link
                                href={ProjectController.index.url(
                                    organization.id,
                                )}
                            >
                                All projects
                            </Link>
                        </Button>
                    </div>
                }
            >
                <div className="grid gap-[18px] xl:grid-cols-[minmax(0,22rem)_1fr]">
                    <aside className="flex flex-col gap-[18px]">
                        <CommandCard title="Overview" dot="crimson">
                            <div className="space-y-4">
                                <div className="flex items-start justify-between gap-2">
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={cn(
                                                'size-2 shrink-0 rounded-full',
                                                healthDotClass[
                                                    project.health
                                                ] ??
                                                    healthDotClass.active,
                                            )}
                                            aria-hidden
                                        />
                                        <StatusPill status={project.health} />
                                    </div>
                                    <Badge variant="outline">
                                        {project.progress_percent}%
                                    </Badge>
                                </div>

                                {project.objective && (
                                    <ExpandableText
                                        text={project.objective}
                                        maxLength={240}
                                        textClassName="text-muted-foreground"
                                    />
                                )}

                                <div>
                                    <div className="mb-2 flex items-center justify-between text-xs font-medium text-muted-foreground">
                                        <span>Progress</span>
                                        <span className="tabular-nums text-primary">
                                            {project.progress_percent}%
                                        </span>
                                    </div>
                                    <div
                                        className="tcm-proj-bar-track"
                                        role="progressbar"
                                        aria-valuenow={
                                            project.progress_percent
                                        }
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                    >
                                        <div
                                            className="tcm-proj-bar-fill"
                                            style={{
                                                width: `${project.progress_percent}%`,
                                            }}
                                        />
                                    </div>
                                </div>

                                {project.next_action && (
                                    <div className="rounded-md border-l-2 border-primary bg-muted/40 px-3 py-2">
                                        <p className="text-sm font-medium text-foreground">
                                            Next
                                        </p>
                                        <ExpandableText
                                            text={project.next_action}
                                            maxLength={120}
                                            textClassName="text-muted-foreground"
                                        />
                                    </div>
                                )}
                            </div>
                        </CommandCard>

                        <CommandCard
                            title="Team"
                            dot="blue"
                            badge={
                                team.length > 0 ? (
                                    <Badge variant="secondary">
                                        {team.length}
                                    </Badge>
                                ) : undefined
                            }
                        >
                            {team.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No team members assigned yet.
                                </p>
                            ) : (
                                <div className="flex flex-wrap gap-1.5">
                                    {team.map((member) => (
                                        <span
                                            key={member.id}
                                            className="inline-flex items-center rounded-full bg-blue-500/10 px-2.5 py-0.5 text-[11px] font-medium text-blue-700 dark:text-blue-300"
                                        >
                                            {member.display_name ?? 'Member'}
                                            {member.role_name && (
                                                <span className="ml-1 text-muted-foreground">
                                                    · {member.role_name}
                                                </span>
                                            )}
                                        </span>
                                    ))}
                                </div>
                            )}
                            {canManageTeam && (
                                <Button
                                    asChild
                                    variant="link"
                                    size="sm"
                                    className="mt-2 h-auto px-0"
                                >
                                    <Link
                                        href={ProjectMemberController.index.url(
                                            [
                                                organization.id,
                                                project.id,
                                            ],
                                        )}
                                    >
                                        Manage team
                                    </Link>
                                </Button>
                            )}
                        </CommandCard>

                        <CommandCard title="Settings" dot="gold">
                            <div className="flex flex-wrap gap-2">
                                {canManageTeam && (
                                    <Button asChild variant="secondary" size="sm">
                                        <Link
                                            href={ProjectMemberController.index.url(
                                                [
                                                    organization.id,
                                                    project.id,
                                                ],
                                            )}
                                        >
                                            Team
                                        </Link>
                                    </Button>
                                )}
                                {canManageRoles && (
                                    <Button asChild variant="secondary" size="sm">
                                        <Link
                                            href={ProjectRoleController.index.url(
                                                [
                                                    organization.id,
                                                    project.id,
                                                ],
                                            )}
                                        >
                                            Roles
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </CommandCard>
                    </aside>

                    <CommandCard
                        title="Board"
                        description="Drag cards between columns to update status. Task type is shown on each card."
                        dot="blue"
                        noPadding
                        className="min-w-0"
                        contentClassName="min-w-0 overflow-x-auto p-3 sm:p-4"
                    >
                        <TaskKindLegend className="mb-3 px-0.5" />
                        <ProjectKanban
                            organizationId={organization.id}
                            tasks={tasks}
                            permissions={permissions}
                        />
                    </CommandCard>
                </div>
            </PageShell>
        </>
    );
}
