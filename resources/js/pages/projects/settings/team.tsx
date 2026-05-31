import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
            <div className="flex flex-col gap-6 p-6">
                <Heading
                    title={`Team · ${project.name}`}
                    description={`Members assigned to this project in ${organization.name}.`}
                />

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Roster</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {team.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No team members yet.
                            </p>
                        ) : (
                            team.map((member) => (
                                <div
                                    key={member.id}
                                    className="flex items-center justify-between gap-4 border-b pb-3 last:border-0 last:pb-0"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {member.display_name ??
                                                'Unknown member'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {member.role.name ??
                                                member.role.slug}
                                        </p>
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Link
                    href={ProjectController.show.url([
                        organization.id,
                        project.id,
                    ])}
                    className="text-sm underline underline-offset-4"
                >
                    Back to project
                </Link>
            </div>
        </>
    );
}
