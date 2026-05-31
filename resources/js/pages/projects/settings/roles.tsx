import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { OrganizationSummary } from '@/types/organization';

type ProjectRoleItem = {
    id: number;
    name: string;
    slug: string;
    is_default: boolean;
    permissions: string[];
};

type ProjectRolesProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    project: { id: number; name: string };
    roles: ProjectRoleItem[];
    permissionGroups: Record<
        string,
        { label: string; permissions: Record<string, string> }
    >;
};

export default function ProjectRolesSettings({
    organization,
    project,
    roles,
}: ProjectRolesProps) {
    return (
        <>
            <Head title={`Roles · ${project.name}`} />
            <div className="flex flex-col gap-6 p-6">
                <Heading
                    title={`Project roles · ${project.name}`}
                    description="Default roles created at project bootstrap. Permission sync UI arrives in a later milestone."
                />

                <div className="grid gap-3">
                    {roles.map((role) => (
                        <Card key={role.id}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    {role.name}
                                    {role.is_default && (
                                        <Badge variant="secondary">
                                            Default
                                        </Badge>
                                    )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    {role.permissions.length} permissions · slug{' '}
                                    <code>{role.slug}</code>
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

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
