import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import { Head, Link } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { EmptyState } from '@/components/command-centre/empty-state';
import { PageShell } from '@/components/command-centre/page-shell';
import { Button } from '@/components/ui/button';
import type { OrganizationSummary } from '@/types/organization';

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

type ProjectTeamProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    project: { id: number; name: string };
    team: TeamMember[];
    roles: Array<{ id: number; name: string; slug: string }>;
};

export default function ProjectTeamSettings({
    organization,
    project,
    team,
}: ProjectTeamProps) {
    return (
        <>
            <Head title={`Team · ${project.name}`} />
            <PageShell
                title={`Team · ${project.name}`}
                subtitle={organization.name}
                stats={[{ label: 'Members', value: team.length }]}
                actions={
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
                }
            >
                <CommandCard title="Roster" dot="blue">
                    {team.length === 0 ? (
                        <EmptyState>No team members yet.</EmptyState>
                    ) : (
                        <ul className="divide-y divide-border/50">
                            {team.map((member) => (
                                <li key={member.id} className="tcm-list-row">
                                    <div>
                                        <p className="font-medium">
                                            {member.display_name ??
                                                'Unknown member'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {member.role.name ??
                                                member.role.slug}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </CommandCard>
            </PageShell>
        </>
    );
}
