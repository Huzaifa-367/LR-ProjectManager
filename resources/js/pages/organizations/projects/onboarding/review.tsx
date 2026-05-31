import AiOnboardingController from '@/actions/App/Http/Controllers/CommandCentre/AiOnboardingController';
import { Head, Link } from '@inertiajs/react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { canOrg } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

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
        tasks?: Array<{ title: string }>;
        decisions?: Array<{ title: string }>;
        reminders?: Array<{ title: string }>;
    };
};

type ReviewProposalProps = {
    organization: OrganizationSummary;
    proposal: OnboardingProposalDetail;
    permissions: CommandCentrePermissions;
};

export default function ReviewProposal({
    organization,
    proposal,
    permissions,
}: ReviewProposalProps) {
    const canUpdate = canOrg(permissions.org, 'org.ai-onboarding.update');
    const canApprove = canOrg(permissions.org, 'org.ai-onboarding.approve');
    const canApply = canOrg(permissions.org, 'org.ai-onboarding.apply');
    const canReject = canOrg(permissions.org, 'org.ai-onboarding.reject');

    return (
        <>
            <Head title="Review AI proposal" />
            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title="Review proposal"
                    description={proposal.summary ?? 'Edit the plan before applying.'}
                />

                <div className="flex items-center gap-2">
                    <Badge variant="outline">v{proposal.version}</Badge>
                    <Badge>{proposal.status.replace('_', ' ')}</Badge>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{proposal.payload.project.name}</CardTitle>
                        <CardDescription>
                            {proposal.payload.project.objective}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <h3 className="text-sm font-medium">Tasks</h3>
                            <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                                {(proposal.payload.tasks ?? []).map((task) => (
                                    <li key={task.title}>{task.title}</li>
                                ))}
                            </ul>
                        </div>
                    </CardContent>
                </Card>

                {canUpdate && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Edit project name</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                {...AiOnboardingController.update.form({
                                    organization: organization.id,
                                    aiOnboardingProposal: proposal.id,
                                })}
                                className="flex flex-col gap-4 sm:flex-row sm:items-end"
                            >
                                <div className="grow space-y-2">
                                    <Label htmlFor="project_name">Name</Label>
                                    <Input
                                        id="project_name"
                                        name="payload[project][name]"
                                        defaultValue={
                                            proposal.payload.project.name
                                        }
                                    />
                                    <input
                                        type="hidden"
                                        name="payload[project][objective]"
                                        value={
                                            proposal.payload.project
                                                .objective ?? ''
                                        }
                                    />
                                    <input
                                        type="hidden"
                                        name="payload[team]"
                                        value="[]"
                                    />
                                    <input
                                        type="hidden"
                                        name="payload[tasks]"
                                        value={JSON.stringify(
                                            proposal.payload.tasks ?? [],
                                        )}
                                    />
                                </div>
                                <Button type="submit" variant="secondary">
                                    Save edits
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <div className="flex flex-wrap gap-2">
                    {canApprove && proposal.status === 'pending_review' && (
                        <form
                            {...AiOnboardingController.approve.form({
                                organization: organization.id,
                                aiOnboardingProposal: proposal.id,
                            })}
                        >
                            <Button type="submit">Approve</Button>
                        </form>
                    )}
                    {canApply && proposal.status === 'approved' && (
                        <form
                            {...AiOnboardingController.apply.form({
                                organization: organization.id,
                                aiOnboardingProposal: proposal.id,
                            })}
                        >
                            <Button type="submit">Apply plan</Button>
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
                    <Button asChild variant="ghost">
                        <Link
                            href={`/organizations/${organization.id}/projects/onboarding`}
                        >
                            Back to wizard
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
