import { Head, Link } from '@inertiajs/react';
import AiOnboardingController from '@/actions/App/Http/Controllers/CommandCentre/AiOnboardingController';
import ProjectOnboardingController from '@/actions/App/Http/Controllers/CommandCentre/ProjectOnboardingController';
import { CommandCard } from '@/components/command-centre/command-card';
import { EmptyState } from '@/components/command-centre/empty-state';
import { ExpandableText } from '@/components/command-centre/expandable-text';
import { OnboardingPlanItem } from '@/components/command-centre/onboarding-plan-item';
import { PageShell } from '@/components/command-centre/page-shell';
import { StatusPill } from '@/components/command-centre/status-pill';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { canOrg } from '@/hooks/use-org-permissions';
import { cn } from '@/lib/utils';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type ProposalTask = {
    title: string;
    description?: string | null;
    priority?: string | null;
    status?: string | null;
    deadline_type?: string | null;
};

type ProposalTeamMember = {
    organization_member_id: number;
    project_role_slug: string;
    display_name?: string | null;
};

type OnboardingProposalDetail = {
    id: number;
    status: string;
    summary: string | null;
    version: number;
    payload: {
        project: {
            name: string;
            objective?: string | null;
            next_action?: string | null;
        };
        team?: ProposalTeamMember[];
        tasks?: ProposalTask[];
        decisions?: Array<{ title: string; sort_order?: number }>;
        reminders?: Array<{ title: string; description?: string | null }>;
    };
};

type ReviewProposalProps = {
    organization: OrganizationSummary;
    proposal: OnboardingProposalDetail;
    permissions: CommandCentrePermissions;
};

const reviewSteps = [
    { key: 'pending_review', label: 'Review' },
    { key: 'approved', label: 'Approved' },
    { key: 'applied', label: 'Applied' },
] as const;

export default function ReviewProposal({
    organization,
    proposal,
    permissions,
}: ReviewProposalProps) {
    const canUpdate = canOrg(permissions.org, 'org.ai-onboarding.update');
    const canApprove = canOrg(permissions.org, 'org.ai-onboarding.approve');
    const canApply = canOrg(permissions.org, 'org.ai-onboarding.apply');
    const canReject = canOrg(permissions.org, 'org.ai-onboarding.reject');

    const tasks = proposal.payload.tasks ?? [];
    const decisions = proposal.payload.decisions ?? [];
    const reminders = proposal.payload.reminders ?? [];
    const team = proposal.payload.team ?? [];
    const workItemCount = tasks.length + decisions.length + reminders.length;

    return (
        <>
            <Head title="Review AI proposal" />
            <PageShell
                title="Review plan"
                accent="proposal"
                subtitle={
                    proposal.summary ??
                    'Human approval required before the Command Centre is updated.'
                }
                stats={[
                    { label: 'Version', value: `v${proposal.version}` },
                    { label: 'Work items', value: workItemCount, tone: 'accent' },
                    { label: 'Tasks', value: tasks.length },
                    { label: 'Team', value: team.length },
                ]}
                actions={
                    <Button asChild variant="outline" size="sm">
                        <Link
                            href={ProjectOnboardingController.create.url(
                                organization.id,
                            )}
                        >
                            Back to brief
                        </Link>
                    </Button>
                }
            >
                <div className="flex flex-wrap items-center gap-2">
                    <StatusPill status={proposal.status} />
                    <Badge variant="outline">v{proposal.version}</Badge>
                </div>

                <CommandCard
                    title="Approval workflow"
                    description="Nothing is created until you apply an approved plan."
                    dot="green"
                >
                    <div className="flex flex-wrap gap-2">
                        {reviewSteps.map((step) => (
                            <Badge
                                key={step.key}
                                variant={
                                    proposal.status === step.key
                                        ? 'default'
                                        : 'outline'
                                }
                                className="text-[10px]"
                            >
                                {step.label}
                            </Badge>
                        ))}
                    </div>
                </CommandCard>

                <CommandCard title="Project metadata" dot="crimson">
                    <div className="space-y-3">
                        <div>
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                Title
                            </p>
                            <p className="mt-1 text-lg font-semibold leading-snug">
                                {proposal.payload.project.name}
                            </p>
                        </div>
                        {proposal.payload.project.objective && (
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Objective
                                </p>
                                <ExpandableText
                                    text={proposal.payload.project.objective}
                                    maxLength={240}
                                    className="mt-1"
                                    textClassName="text-muted-foreground"
                                />
                            </div>
                        )}
                        {proposal.payload.project.next_action && (
                            <div className="rounded-md border border-border/60 bg-muted/30 px-3 py-2">
                                <p className="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                                    First action
                                </p>
                                <p className="mt-1 text-sm font-medium">
                                    {proposal.payload.project.next_action}
                                </p>
                            </div>
                        )}
                    </div>
                </CommandCard>

                {team.length > 0 && (
                    <CommandCard
                        title="Project team & RBAC bootstrap"
                        description="Project roles and permissions are created automatically on apply."
                        dot="green"
                    >
                        <ul className="divide-y divide-border/50">
                            {team.map((member) => (
                                <li
                                    key={member.organization_member_id}
                                    className="tcm-list-row"
                                >
                                    <span className="text-sm font-medium">
                                        {member.display_name ??
                                            `Member #${member.organization_member_id}`}
                                    </span>
                                    <Badge variant="outline" className="text-[10px]">
                                        {member.project_role_slug.replace(
                                            /_/g,
                                            ' ',
                                        )}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    </CommandCard>
                )}

                <CommandCard
                    title="Unified work items"
                    description="Tasks, decisions, and reminders land in the same Command Centre table with different kinds."
                    dot="blue"
                >
                    {workItemCount === 0 ? (
                        <EmptyState>No work items in this proposal.</EmptyState>
                    ) : (
                        <ul className="divide-y divide-border/50">
                            {tasks.map((task, index) => (
                                <OnboardingPlanItem
                                    key={`task-${task.title}-${index}`}
                                    kind="task"
                                    title={task.title}
                                    description={task.description}
                                    priority={task.priority}
                                    deadlineType={task.deadline_type}
                                />
                            ))}
                            {decisions.map((decision, index) => (
                                <OnboardingPlanItem
                                    key={`decision-${decision.title}-${index}`}
                                    kind="decision"
                                    title={decision.title}
                                />
                            ))}
                            {reminders.map((reminder, index) => (
                                <OnboardingPlanItem
                                    key={`reminder-${reminder.title}-${index}`}
                                    kind="reminder"
                                    title={reminder.title}
                                    description={reminder.description}
                                />
                            ))}
                        </ul>
                    )}
                </CommandCard>

                {canUpdate && (
                    <CommandCard
                        title="Edit project title"
                        description="Short titles work best on dashboards. Other payload fields are preserved."
                        dot="gold"
                    >
                        <form
                            {...AiOnboardingController.update.form({
                                organization: organization.id,
                                aiOnboardingProposal: proposal.id,
                            })}
                            className="flex flex-col gap-4 sm:flex-row sm:items-end"
                        >
                            <div className="grow space-y-2">
                                <Label htmlFor="project_name">Project title</Label>
                                <Input
                                    id="project_name"
                                    name="payload[project][name]"
                                    defaultValue={
                                        proposal.payload.project.name
                                    }
                                    maxLength={55}
                                />
                                <input
                                    type="hidden"
                                    name="payload[project][objective]"
                                    value={
                                        proposal.payload.project.objective ??
                                        ''
                                    }
                                />
                                <input
                                    type="hidden"
                                    name="payload[project][next_action]"
                                    value={
                                        proposal.payload.project.next_action ??
                                        ''
                                    }
                                />
                                <input
                                    type="hidden"
                                    name="payload[team]"
                                    value={JSON.stringify(team)}
                                />
                                <input
                                    type="hidden"
                                    name="payload[tasks]"
                                    value={JSON.stringify(tasks)}
                                />
                                <input
                                    type="hidden"
                                    name="payload[decisions]"
                                    value={JSON.stringify(decisions)}
                                />
                                <input
                                    type="hidden"
                                    name="payload[reminders]"
                                    value={JSON.stringify(reminders)}
                                />
                            </div>
                            <Button type="submit" variant="secondary">
                                Save title
                            </Button>
                        </form>
                    </CommandCard>
                )}

                <CommandCard title="Actions" dot="green">
                    <div className="flex flex-wrap gap-2">
                        {canApprove &&
                            proposal.status === 'pending_review' && (
                                <form
                                    {...AiOnboardingController.approve.form({
                                        organization: organization.id,
                                        aiOnboardingProposal: proposal.id,
                                    })}
                                >
                                    <Button type="submit">Approve plan</Button>
                                </form>
                            )}
                        {canApply && proposal.status === 'approved' && (
                            <form
                                {...AiOnboardingController.apply.form({
                                    organization: organization.id,
                                    aiOnboardingProposal: proposal.id,
                                })}
                            >
                                <Button type="submit">
                                    Apply to Command Centre
                                </Button>
                            </form>
                        )}
                        {canReject && (
                            <form
                                {...AiOnboardingController.reject.form({
                                    organization: organization.id,
                                    aiOnboardingProposal: proposal.id,
                                })}
                            >
                                <Button type="submit" variant="outline">
                                    Reject
                                </Button>
                            </form>
                        )}
                    </div>
                    <p
                        className={cn(
                            'mt-3 text-xs text-muted-foreground',
                            proposal.status === 'approved' && 'text-primary',
                        )}
                    >
                        {proposal.status === 'pending_review' &&
                            'Approve to unlock apply. Apply creates the project, team roles, and all work items.'}
                        {proposal.status === 'approved' &&
                            'Ready to commit — apply will bootstrap project RBAC and insert tasks, decisions, and reminders.'}
                        {proposal.status === 'applied' &&
                            'This plan has already been applied.'}
                    </p>
                </CommandCard>
            </PageShell>
        </>
    );
}
