import ActivityLogController from '@/actions/App/Http/Controllers/ActivityLogController';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationMailProfileController from '@/actions/App/Http/Controllers/OrganizationMailProfileController';
import OrganizationReportsController from '@/actions/App/Http/Controllers/OrganizationReportsController';
import NotificationPreferenceController from '@/actions/App/Http/Controllers/NotificationPreferenceController';
import OrganizationMemberController from '@/actions/App/Http/Controllers/OrganizationMemberController';
import OrganizationRoleController from '@/actions/App/Http/Controllers/OrganizationRoleController';
import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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

type OrganizationSettingsProps = {
    organization: OrganizationSummary & {
        settings: {
            timezone: string;
            focus_cap: number;
            ai_enabled: boolean;
        };
    };
    permissions: CommandCentrePermissions;
};

export default function OrganizationSettingsIndex({
    organization,
    permissions,
}: OrganizationSettingsProps) {
    const canUpdate = canOrg(permissions.org, 'org.organizations.update');
    const canViewMembers = canOrg(permissions.org, 'org.members.index');
    const canViewRoles = canOrg(permissions.org, 'org.roles.index');
    const canViewActivity = canOrg(permissions.org, 'org.activity-logs.index');
    const canViewMail = canOrg(permissions.org, 'org.mail-profiles.index');
    const canViewNotifications = canOrg(
        permissions.org,
        'org.notification-preferences.show',
    );
    const canViewReports = canOrg(
        permissions.org,
        'org.notification-deliveries.index',
    );

    return (
        <>
            <Head title={`Settings · ${organization.name}`} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-6">
                <Heading
                    title={`Organization settings`}
                    description={`Manage profile and workspace defaults for ${organization.name}.`}
                />

                <div className="flex flex-wrap gap-3 text-sm">
                    {canViewMembers && (
                        <Link
                            href={OrganizationMemberController.index.url(
                                organization.id,
                            )}
                            className="underline underline-offset-4"
                        >
                            Members
                        </Link>
                    )}
                    {canViewRoles && (
                        <Link
                            href={OrganizationRoleController.index.url(
                                organization.id,
                            )}
                            className="underline underline-offset-4"
                        >
                            Roles & permissions
                        </Link>
                    )}
                    {canViewActivity && (
                        <Link
                            href={ActivityLogController.index.url(
                                organization.id,
                            )}
                            className="underline underline-offset-4"
                        >
                            Activity log
                        </Link>
                    )}
                    {canViewMail && (
                        <Link
                            href={OrganizationMailProfileController.index.url(
                                organization.id,
                            )}
                            className="underline underline-offset-4"
                        >
                            Mail
                        </Link>
                    )}
                    {canViewNotifications && (
                        <Link
                            href={NotificationPreferenceController.show.url(
                                organization.id,
                            )}
                            className="underline underline-offset-4"
                        >
                            Notifications
                        </Link>
                    )}
                    {canViewReports && (
                        <Link
                            href={OrganizationReportsController.index.url(
                                organization.id,
                            )}
                            className="underline underline-offset-4"
                        >
                            Reports
                        </Link>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Profile</CardTitle>
                        <CardDescription>
                            Organization name and command centre defaults.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {canUpdate ? (
                            <Form
                                {...OrganizationController.update.form(
                                    organization.id,
                                )}
                                className="flex flex-col gap-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">Name</Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                defaultValue={organization.name}
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="timezone">
                                                Timezone
                                            </Label>
                                            <Input
                                                id="timezone"
                                                name="settings[timezone]"
                                                defaultValue={
                                                    organization.settings
                                                        .timezone
                                                }
                                            />
                                            <InputError
                                                message={errors['settings.timezone']}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="focus_cap">
                                                Daily focus cap
                                            </Label>
                                            <Input
                                                id="focus_cap"
                                                name="settings[focus_cap]"
                                                type="number"
                                                min={1}
                                                max={10}
                                                defaultValue={
                                                    organization.settings
                                                        .focus_cap
                                                }
                                            />
                                            <InputError
                                                message={errors['settings.focus_cap']}
                                            />
                                        </div>
                                        <label className="flex items-center gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                name="settings[ai_enabled]"
                                                value="1"
                                                defaultChecked={
                                                    organization.settings
                                                        .ai_enabled
                                                }
                                            />
                                            AI features enabled
                                        </label>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Save changes
                                        </Button>
                                    </>
                                )}
                            </Form>
                        ) : (
                            <dl className="space-y-3 text-sm">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Name
                                    </dt>
                                    <dd className="font-medium">
                                        {organization.name}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Timezone
                                    </dt>
                                    <dd>{organization.settings.timezone}</dd>
                                </div>
                            </dl>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
