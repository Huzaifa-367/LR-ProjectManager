import { Form, Head, Link } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import AiOnboardingController from '@/actions/App/Http/Controllers/CommandCentre/AiOnboardingController';
import ProjectOnboardingController from '@/actions/App/Http/Controllers/CommandCentre/ProjectOnboardingController';
import { CommandCard } from '@/components/command-centre/command-card';
import { EmptyState } from '@/components/command-centre/empty-state';
import { FormBusyOverlay } from '@/components/command-centre/form-busy-overlay';
import { PageShell } from '@/components/command-centre/page-shell';
import { StatusPill } from '@/components/command-centre/status-pill';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Spinner } from '@/components/ui/spinner';
import { canOrg } from '@/hooks/use-org-permissions';
import { useOrganizationContext } from '@/hooks/use-organization-context';
import { cn } from '@/lib/utils';
import {
    applySuggestion,
    isSuggestionSelected,
    type QuestionSuggestion,
} from '@/lib/onboarding-answers';
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

type ConversationMessage = {
    id: number;
    role: string;
    content: string | null;
};

type ContextQuestion = {
    key: string;
    label: string;
    prompt: string;
    hint: string;
    required: boolean;
    input_mode: 'single' | 'list';
    suggestions: QuestionSuggestion[];
};

type ProjectProfile = {
    key: string;
    label: string;
    summary: string;
};

type ContextAssessment = {
    is_complete: boolean;
    phase: 'initial' | 'follow_up' | 'ready';
    project_profile: ProjectProfile;
    detected: Record<string, string | null>;
    missing_required: string[];
    missing_optional: string[];
    questions: ContextQuestion[];
    readiness_percent: number;
};

type RequirementDefinition = {
    key: string;
    label: string;
    required: boolean;
    prompt: string;
    hint: string;
};

type WizardStep = {
    label: string;
    detail: string;
};

type ExampleBrief = {
    title: string;
    profile: string;
    brief: string;
};

type ProjectOnboardingProps = {
    organization: OrganizationSummary;
    session: AiSessionSummary;
    members: MemberOption[];
    proposal: OnboardingProposal | null;
    conversation: ConversationMessage[];
    contextAssessment: ContextAssessment;
    requirements: RequirementDefinition[];
    wizardSteps: WizardStep[];
    initialPastePlaceholder: string;
    initialPasteGuide: string;
    exampleBriefs: ExampleBrief[];
    permissions: CommandCentrePermissions;
};

export default function ProjectOnboarding({
    organization,
    session,
    members,
    proposal,
    conversation,
    contextAssessment,
    requirements,
    wizardSteps,
    initialPastePlaceholder,
    initialPasteGuide,
    exampleBriefs,
    permissions,
}: ProjectOnboardingProps) {
    const [brief, setBrief] = useState('');
    const [answers, setAnswers] = useState<Record<string, string>>({});
    const [showAdditionalDetails, setShowAdditionalDetails] = useState(false);
    const [selectedMemberIds, setSelectedMemberIds] = useState<number[]>(() =>
        members.slice(0, 3).map((member) => member.id),
    );
    const { aiEnabled } = useOrganizationContext();
    const canPropose = canOrg(permissions.org, 'org.ai-onboarding.propose');

    const phase = contextAssessment.phase;
    const hasConversation = conversation.length > 0;
    const isInitialStep = phase === 'initial' && !proposal;
    const isFollowUpStep =
        phase === 'follow_up' &&
        !contextAssessment.is_complete &&
        contextAssessment.questions.length > 0;
    const projectProfile = contextAssessment.project_profile;

    const currentStep = proposal ? 2 : isFollowUpStep ? 1 : 0;

    useEffect(() => {
        setBrief('');
        setAnswers({});
        setShowAdditionalDetails(false);
    }, [session.id]);

    const selectedMembers = useMemo(
        () =>
            selectedMemberIds
                .map((id) => members.find((member) => member.id === id))
                .filter((member): member is MemberOption => member !== undefined),
        [members, selectedMemberIds],
    );

    const toggleMember = (memberId: number, checked: boolean): void => {
        setSelectedMemberIds((current) => {
            if (checked) {
                return current.includes(memberId)
                    ? current
                    : [...current, memberId];
            }

            return current.filter((id) => id !== memberId);
        });
    };

    const updateAnswer = (key: string, value: string): void => {
        setAnswers((current) => ({ ...current, [key]: value }));
    };

    const textQuestions = contextAssessment.questions.filter(
        (question) => question.key !== 'team',
    );

    return (
        <>
            <Head title="AI project onboarding" />
            <PageShell
                title="AI project onboarding"
                accent="onboarding"
                subtitle={`Describe ${organization.name}'s project — works for software, training, workshops, events, and more.`}
                stats={[
                    {
                        label: 'Readiness',
                        value: `${contextAssessment.readiness_percent}%`,
                        tone: 'accent',
                    },
                    { label: 'Selected team', value: selectedMembers.length },
                    {
                        label: 'Step',
                        value: wizardSteps[currentStep]?.label ?? 'Details',
                    },
                ]}
                actions={
                    <Form
                        {...ProjectOnboardingController.reset.form({
                            organization: organization.id,
                        })}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="outline"
                                size="sm"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Start fresh
                            </Button>
                        )}
                    </Form>
                }
            >
                {!aiEnabled && (
                    <CommandCard title="AI disabled" dot="gold">
                        <EmptyState>
                            AI features are turned off for this organization.
                            An owner can re-enable them under Settings →
                            Organization profile.
                        </EmptyState>
                    </CommandCard>
                )}

                {hasConversation && projectProfile && (
                    <CommandCard
                        title="Detected project type"
                        description={projectProfile.summary}
                        dot="blue"
                    >
                        <Badge variant="secondary" className="text-xs">
                            {projectProfile.label}
                        </Badge>
                    </CommandCard>
                )}

                <CommandCard
                    title="Context checklist"
                    description="The plan is generated only from what you provide — nothing is invented."
                    dot="green"
                >
                    <div className="mb-4 flex flex-wrap gap-2">
                        {wizardSteps.map((step, index) => (
                            <Badge
                                key={step.label}
                                variant={
                                    index === currentStep ? 'default' : 'outline'
                                }
                                className="text-[10px]"
                            >
                                {index + 1}. {step.label}
                            </Badge>
                        ))}
                    </div>
                    <ul className="grid gap-2 sm:grid-cols-2">
                        {requirements.map((requirement) => {
                            const detected =
                                contextAssessment.detected[requirement.key];

                            return (
                                <li
                                    key={requirement.key}
                                    className="rounded-lg border border-border/60 px-3 py-2"
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="text-sm font-medium">
                                            {requirement.label}
                                        </span>
                                        <Badge
                                            variant={
                                                detected ? 'secondary' : 'outline'
                                            }
                                            className="text-[10px]"
                                        >
                                            {detected
                                                ? 'Provided'
                                                : requirement.required
                                                  ? 'Required'
                                                  : 'Optional'}
                                        </Badge>
                                    </div>
                                    {detected && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {detected}
                                        </p>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                </CommandCard>

                {conversation.length > 0 && (
                    <CommandCard title="Conversation" dot="blue">
                        <ul className="space-y-3">
                            {conversation.map((message) => (
                                <li
                                    key={message.id}
                                    className={cn(
                                        'rounded-lg px-3 py-2 text-sm',
                                        message.role === 'assistant'
                                            ? 'bg-muted/50 text-muted-foreground'
                                            : 'bg-primary/5',
                                    )}
                                >
                                    <p className="mb-1 text-[10px] font-semibold tracking-wide uppercase">
                                        {message.role === 'assistant'
                                            ? 'Assistant'
                                            : 'You'}
                                    </p>
                                    <p className="whitespace-pre-wrap">
                                        {message.content}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </CommandCard>
                )}

                {proposal && (
                    <CommandCard
                        title="Latest proposal"
                        description={
                            proposal.summary ??
                            'Your generated plan is ready to review.'
                        }
                        dot="gold"
                        action={
                            <Button asChild size="sm">
                                <Link
                                    href={AiOnboardingController.show.url([
                                        organization.id,
                                        proposal.id,
                                    ])}
                                >
                                    Review proposal
                                </Link>
                            </Button>
                        }
                    >
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusPill status={proposal.status} />
                            <span className="text-xs text-muted-foreground">
                                Session #{session.id}
                            </span>
                        </div>
                    </CommandCard>
                )}

                <CommandCard
                    title={
                        isFollowUpStep
                            ? 'Follow-up questions'
                            : 'Project details'
                    }
                    description={
                        isFollowUpStep
                            ? 'Answer the tailored questions below — required items first, then optional context for your project type.'
                            : initialPasteGuide
                    }
                    dot="crimson"
                >
                    {canPropose && aiEnabled ? (
                        <Form
                            {...AiOnboardingController.propose.form({
                                organization: organization.id,
                            })}
                            className="space-y-4"
                        >
                            {({ processing }) => {
                                const isGeneratingPlan =
                                    isFollowUpStep &&
                                    (contextAssessment.is_complete ||
                                        contextAssessment.readiness_percent >=
                                            80);

                                return (
                                    <>
                                        <FormBusyOverlay
                                            visible={processing}
                                            title={
                                                isGeneratingPlan
                                                    ? 'Generating your plan'
                                                    : isFollowUpStep
                                                      ? 'Processing your answers'
                                                      : 'Analyzing your brief'
                                            }
                                            description={
                                                isGeneratingPlan
                                                    ? 'AI is building tasks, decisions, and reminders. This may take up to a minute.'
                                                    : 'Hang tight while we review what you provided.'
                                            }
                                        />
                                        <input
                                            type="hidden"
                                            name="ai_session_id"
                                            value={session.id}
                                        />

                                        {isInitialStep && (
                                            <div className="space-y-2">
                                                <Label htmlFor="brief">
                                                    Paste project details
                                                </Label>
                                                {exampleBriefs.length > 0 && (
                                                    <div className="space-y-2">
                                                        <p className="text-xs text-muted-foreground">
                                                            Try an example —
                                                            tap to fill the
                                                            brief:
                                                        </p>
                                                        <div className="flex flex-wrap gap-2">
                                                            {exampleBriefs.map(
                                                                (
                                                                    example,
                                                                ) => (
                                                                    <Button
                                                                        key={
                                                                            example.title
                                                                        }
                                                                        type="button"
                                                                        variant="outline"
                                                                        size="sm"
                                                                        disabled={
                                                                            processing
                                                                        }
                                                                        className="h-auto max-w-full px-2.5 py-1 text-left text-xs whitespace-normal"
                                                                        onClick={() =>
                                                                            setBrief(
                                                                                example.brief,
                                                                            )
                                                                        }
                                                                    >
                                                                        {
                                                                            example.title
                                                                        }
                                                                    </Button>
                                                                ),
                                                            )}
                                                        </div>
                                                    </div>
                                                )}
                                                <Textarea
                                                    id="brief"
                                                    name="brief"
                                                    value={brief}
                                                    onChange={(event) =>
                                                        setBrief(
                                                            event.target.value,
                                                        )
                                                    }
                                                    rows={14}
                                                    disabled={processing}
                                                    placeholder={
                                                        initialPastePlaceholder
                                                    }
                                                />
                                            </div>
                                        )}

                                        {isFollowUpStep && (
                                            <>
                                                <div className="space-y-4">
                                                    {textQuestions.map(
                                                        (question) => (
                                                            <div
                                                                key={
                                                                    question.key
                                                                }
                                                                className="space-y-2 rounded-lg border border-border/60 p-3"
                                                            >
                                                                <div className="flex flex-wrap items-center gap-2">
                                                                    <Label
                                                                        htmlFor={`answer-${question.key}`}
                                                                        className="text-sm font-semibold"
                                                                    >
                                                                        {
                                                                            question.label
                                                                        }
                                                                    </Label>
                                                                    <Badge
                                                                        variant={
                                                                            question.required
                                                                                ? 'default'
                                                                                : 'outline'
                                                                        }
                                                                        className="text-[10px]"
                                                                    >
                                                                        {question.required
                                                                            ? 'Required'
                                                                            : 'Optional'}
                                                                    </Badge>
                                                                </div>
                                                                <p className="text-sm text-muted-foreground">
                                                                    {
                                                                        question.prompt
                                                                    }
                                                                </p>
                                                                {question
                                                                    .suggestions
                                                                    ?.length >
                                                                    0 && (
                                                                    <div className="space-y-2">
                                                                        <p className="text-xs text-muted-foreground">
                                                                            Quick
                                                                            picks
                                                                            — tap
                                                                            to
                                                                            {question.input_mode ===
                                                                            'list'
                                                                                ? ' add or remove'
                                                                                : ' fill'}
                                                                            :
                                                                        </p>
                                                                        <div className="flex flex-wrap gap-2">
                                                                            {question.suggestions.map(
                                                                                (
                                                                                    suggestion,
                                                                                ) => {
                                                                                    const currentAnswer =
                                                                                        answers[
                                                                                            question
                                                                                                .key
                                                                                        ] ??
                                                                                        '';
                                                                                    const isSelected =
                                                                                        isSuggestionSelected(
                                                                                            currentAnswer,
                                                                                            suggestion,
                                                                                        );

                                                                                    return (
                                                                                        <Button
                                                                                            key={`${question.key}-${suggestion.label}`}
                                                                                            type="button"
                                                                                            variant={
                                                                                                isSelected
                                                                                                    ? 'default'
                                                                                                    : 'outline'
                                                                                            }
                                                                                            size="sm"
                                                                                            disabled={
                                                                                                processing
                                                                                            }
                                                                                            className="h-auto max-w-full px-2.5 py-1 text-left text-xs whitespace-normal"
                                                                                            onClick={() =>
                                                                                                updateAnswer(
                                                                                                    question.key,
                                                                                                    applySuggestion(
                                                                                                        currentAnswer,
                                                                                                        suggestion,
                                                                                                    ),
                                                                                                )
                                                                                            }
                                                                                        >
                                                                                            {
                                                                                                suggestion.label
                                                                                            }
                                                                                        </Button>
                                                                                    );
                                                                                },
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                )}
                                                                <Textarea
                                                                    id={`answer-${question.key}`}
                                                                    name={`answers[${question.key}]`}
                                                                    value={
                                                                        answers[
                                                                            question
                                                                                .key
                                                                        ] ?? ''
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        updateAnswer(
                                                                            question.key,
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                    rows={4}
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                    placeholder={
                                                                        question.hint
                                                                    }
                                                                />
                                                            </div>
                                                        ),
                                                    )}
                                                </div>

                                                <div className="space-y-2">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        disabled={processing}
                                                        className="h-auto px-0 text-muted-foreground"
                                                        onClick={() =>
                                                            setShowAdditionalDetails(
                                                                (current) =>
                                                                    !current,
                                                            )
                                                        }
                                                    >
                                                        {showAdditionalDetails
                                                            ? 'Hide additional details'
                                                            : 'Add more project details (optional)'}
                                                    </Button>
                                                    {showAdditionalDetails && (
                                                        <Textarea
                                                            id="brief-extra"
                                                            name="brief"
                                                            value={brief}
                                                            onChange={(event) =>
                                                                setBrief(
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            rows={6}
                                                            disabled={
                                                                processing
                                                            }
                                                            placeholder="Paste extra notes, links, or bullets not covered above…"
                                                        />
                                                    )}
                                                </div>
                                            </>
                                        )}

                                        {selectedMembers.map(
                                            (member, index) => (
                                                <div
                                                    key={member.id}
                                                    className="hidden"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name={`team[${index}][organization_member_id]`}
                                                        value={member.id}
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name={`team[${index}][project_role_slug]`}
                                                        value={
                                                            index === 0
                                                                ? 'project_lead'
                                                                : 'contributor'
                                                        }
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name={`team[${index}][display_name]`}
                                                        value={
                                                            member.display_name
                                                        }
                                                    />
                                                </div>
                                            ),
                                        )}

                                        <Button
                                            type="submit"
                                            disabled={
                                                processing ||
                                                selectedMembers.length === 0 ||
                                                (isInitialStep &&
                                                    brief.trim() === '')
                                            }
                                        >
                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <Sparkles className="size-4" />
                                            )}
                                            {processing
                                                ? isGeneratingPlan
                                                    ? 'Generating plan…'
                                                    : isFollowUpStep
                                                      ? 'Processing…'
                                                      : 'Analyzing…'
                                                : isFollowUpStep
                                                  ? 'Continue to plan'
                                                  : 'Analyze & continue'}
                                        </Button>
                                    </>
                                );
                            }}
                        </Form>
                    ) : (
                        <EmptyState>
                            {!aiEnabled
                                ? 'Enable AI in organization settings to generate plans.'
                                : 'You do not have permission to generate plans.'}
                        </EmptyState>
                    )}
                </CommandCard>

                <CommandCard
                    title="Project team"
                    description="Select members for the project team and default assignees."
                    dot="blue"
                >
                    {members.length === 0 ? (
                        <EmptyState>
                            No active members in this organization.
                        </EmptyState>
                    ) : (
                        <ul className="divide-y divide-border/50">
                            {members.map((member) => {
                                const isSelected = selectedMemberIds.includes(
                                    member.id,
                                );
                                const leadId = selectedMemberIds[0];

                                return (
                                    <li
                                        key={member.id}
                                        className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                    >
                                        <label
                                            htmlFor={`member-${member.id}`}
                                            className="flex min-w-0 flex-1 cursor-pointer items-center gap-3"
                                        >
                                            <Checkbox
                                                id={`member-${member.id}`}
                                                checked={isSelected}
                                                onCheckedChange={(checked) =>
                                                    toggleMember(
                                                        member.id,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <span className="text-sm font-medium">
                                                {member.display_name}
                                            </span>
                                        </label>
                                        <span
                                            className={cn(
                                                'text-[10px] font-semibold tracking-wide uppercase',
                                                member.id === leadId &&
                                                    isSelected
                                                    ? 'text-primary'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            {member.id === leadId && isSelected
                                                ? 'Project lead'
                                                : isSelected
                                                  ? 'Contributor'
                                                  : 'Not included'}
                                        </span>
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
