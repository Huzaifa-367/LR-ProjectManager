import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import ProjectOnboardingController from '@/actions/App/Http/Controllers/CommandCentre/ProjectOnboardingController';
import { Head, Link } from '@inertiajs/react';
import { FolderKanban, Sparkles } from 'lucide-react';
import { CommandCard } from '@/components/command-centre/command-card';
import { CreateProjectDialog } from '@/components/command-centre/create-project-dialog';
import { EmptyState } from '@/components/command-centre/empty-state';
import { PageShell } from '@/components/command-centre/page-shell';
import { StatusPill } from '@/components/command-centre/status-pill';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
import { useOrganizationContext } from '@/hooks/use-organization-context';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type ProjectListItem = {
    id: number;
    name: string;
    objective: string | null;
    progress_percent: number;
    next_action: string | null;
    health: string;
};

type ProjectsIndexProps = {
    organization: OrganizationSummary;
    projects: ProjectListItem[];
    permissions: CommandCentrePermissions;
};

export default function ProjectsIndex({
    organization,
    projects,
    permissions,
}: ProjectsIndexProps) {
    const { aiEnabled } = useOrganizationContext();
    const canCreate = canOrg(permissions.org, 'org.projects.store');
    const canStartOnboarding =
        aiEnabled && canOrg(permissions.org, 'org.ai-onboarding.start');

    return (
        <>
            <Head title="Projects" />
            <PageShell
                title="Projects"
                breadcrumbs={[
                    {
                        title: organization.name,
                        href: `/organizations/${organization.id}/command-centre`,
                    },
                    {
                        title: 'Projects',
                        href: ProjectController.index.url(organization.id),
                    },
                ]}
                stats={[
                    { label: 'Active', value: projects.length },
                ]}
                actions={
                    <div className="flex flex-wrap gap-2">
                        {canStartOnboarding && (
                            <Button asChild variant="secondary" size="sm">
                                <Link
                                    href={ProjectOnboardingController.create.url(
                                        organization.id,
                                    )}
                                >
                                    <Sparkles className="size-4" />
                                    AI onboarding
                                </Link>
                            </Button>
                        )}
                        {canCreate && (
                            <CreateProjectDialog
                                organizationId={organization.id}
                            />
                        )}
                    </div>
                }
            >
                {projects.length === 0 ? (
                    <CommandCard title="No projects yet" dot="gold">
                        <EmptyState>
                            {canStartOnboarding
                                ? 'Start with AI onboarding to draft a project plan, or create one manually.'
                                : canCreate
                                  ? 'Create your first project to start tracking work.'
                                  : 'No projects are visible in your scope.'}
                        </EmptyState>
                        {canStartOnboarding && (
                            <Button asChild className="mt-4" size="sm">
                                <Link
                                    href={ProjectOnboardingController.create.url(
                                        organization.id,
                                    )}
                                >
                                    <Sparkles className="size-4" />
                                    Open AI onboarding
                                </Link>
                            </Button>
                        )}
                    </CommandCard>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {projects.map((project) => (
                            <Link
                                key={project.id}
                                href={ProjectController.show.url([
                                    organization.id,
                                    project.id,
                                ])}
                                className="group rounded-xl border border-border/70 bg-card p-4 transition-colors hover:border-border hover:bg-muted/20"
                            >
                                <div className="mb-3 flex items-start justify-between gap-2">
                                    <div className="flex size-9 items-center justify-center rounded-lg bg-muted/60">
                                        <FolderKanban className="size-4 text-muted-foreground" />
                                    </div>
                                    <StatusPill status={project.health} />
                                </div>
                                <p className="font-medium group-hover:text-primary">
                                    {project.name}
                                </p>
                                {project.objective && (
                                    <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                        {project.objective}
                                    </p>
                                )}
                                <p className="mt-3 text-[11px] font-semibold text-muted-foreground">
                                    {project.progress_percent}% complete
                                </p>
                            </Link>
                        ))}
                    </div>
                )}
            </PageShell>
        </>
    );
}
