import AiOnboardingController from '@/actions/App/Http/Controllers/CommandCentre/AiOnboardingController';
import { Head, Link } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
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
import { Textarea } from '@/components/ui/textarea';
import { canOrg } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type AiSessionSummary = {
    id: number;
    context: string;
    title: string | null;
    status: string;
};

type OnboardingProposal = {
    id: number;
    status: string;
    summary: string | null;
};

type MemberOption = {
    id: number;
    display_name: string;
};

type ProjectOnboardingProps = {
    organization: OrganizationSummary;
    session: AiSessionSummary;
    members: MemberOption[];
    proposal: OnboardingProposal | null;
    permissions: CommandCentrePermissions;
};

export default function ProjectOnboarding({
    organization,
    session,
    members,
    proposal,
    permissions,
}: ProjectOnboardingProps) {
    const [brief, setBrief] = useState('');
    const canPropose = canOrg(permissions.org, 'org.ai-onboarding.propose');

    return (
        <>
            <Head title="AI project onboarding" />
            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title="AI project onboarding"
                    description={`Describe your project goals for ${organization.name}.`}
                />

                {proposal && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Latest proposal</CardTitle>
                            <CardDescription>
                                {proposal.summary ?? 'Review your generated plan.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild variant="outline">
                                <Link
                                    href={`/organizations/${organization.id}/ai-onboarding/${proposal.id}`}
                                >
                                    Review proposal
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Sparkles className="size-4" />
                            Project brief
                        </CardTitle>
                        <CardDescription>
                            Step 1 — describe objectives, timeline, and constraints.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {canPropose ? (
                            <form
                                {...AiOnboardingController.propose.form({
                                    organization: organization.id,
                                })}
                                className="space-y-4"
                            >
                                <input
                                    type="hidden"
                                    name="ai_session_id"
                                    value={session.id}
                                />
                                <div className="space-y-2">
                                    <Label htmlFor="brief">Brief</Label>
                                    <Textarea
                                        id="brief"
                                        name="brief"
                                        value={brief}
                                        onChange={(event) =>
                                            setBrief(event.target.value)
                                        }
                                        rows={6}
                                        placeholder="Launch TAP in three new markets by Q4…"
                                        required
                                    />
                                </div>
                                <input
                                    type="hidden"
                                    name="team[0][organization_member_id]"
                                    value={members[0]?.id ?? ''}
                                />
                                <input
                                    type="hidden"
                                    name="team[0][project_role_slug]"
                                    value="project_lead"
                                />
                                <Button type="submit">Generate plan</Button>
                            </form>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                You do not have permission to generate plans.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Team roster</CardTitle>
                        <CardDescription>
                            Active members available for assignment in the
                            proposal.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {members.map((member) => (
                            <div
                                key={member.id}
                                className="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
                            >
                                <span>{member.display_name}</span>
                                <Input
                                    className="hidden"
                                    readOnly
                                    value={member.id}
                                />
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
