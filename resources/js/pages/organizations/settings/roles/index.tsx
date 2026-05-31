import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationRoleController from '@/actions/App/Http/Controllers/OrganizationRoleController';
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
import { canOrg } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type OrgRoleListItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_system: boolean;
    permissions_count: number;
    members_count: number;
};

type OrganizationRolesIndexProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    roles: OrgRoleListItem[];
    permissions: CommandCentrePermissions;
};

export default function OrganizationRolesIndex({
    organization,
    roles,
    permissions,
}: OrganizationRolesIndexProps) {
    const canShow = canOrg(permissions.org, 'org.roles.show');

    return (
        <>
            <Head title={`Roles · ${organization.name}`} />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Organization roles"
                        description="Default roles created at organization bootstrap. Edit permission matrices per role."
                    />
                    <Link
                        href={OrganizationController.show.url(organization.id)}
                        className="text-sm underline underline-offset-4"
                    >
                        Settings
                    </Link>
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                    {roles.map((role) => (
                        <Card key={role.id}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    {role.name}
                                    {role.is_system && (
                                        <Badge variant="secondary">
                                            System
                                        </Badge>
                                    )}
                                </CardTitle>
                                <CardDescription>
                                    {role.permissions_count} permissions ·{' '}
                                    {role.members_count} members
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {canShow ? (
                                    <Button asChild variant="outline">
                                        <Link
                                            href={OrganizationRoleController.show.url(
                                                [
                                                    organization.id,
                                                    role.id,
                                                ],
                                            )}
                                        >
                                            Edit permissions
                                        </Link>
                                    </Button>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        Slug: {role.slug}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
