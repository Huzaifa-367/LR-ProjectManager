import NotificationPreferenceController from '@/actions/App/Http/Controllers/NotificationPreferenceController';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationMailProfileController from '@/actions/App/Http/Controllers/OrganizationMailProfileController';
import { Form, Head, Link } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { SettingsShell } from '@/components/command-centre/settings-shell';
import { SubmitButton } from '@/components/submit-button';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
import { buildOrganizationSettingsNav } from '@/lib/organization-settings-nav';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type PreferenceMatrixRow = {
    event_type: string;
    label: string;
    channels: {
        database: boolean;
        mail: boolean;
    };
};

type NotificationPreferencesProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    matrix: PreferenceMatrixRow[];
    permissions: CommandCentrePermissions;
};

export default function OrganizationNotificationPreferences({
    organization,
    matrix,
    permissions,
}: NotificationPreferencesProps) {
    const canUpdate = canOrg(
        permissions.org,
        'org.notification-preferences.update',
    );
    const canViewMail = canOrg(
        permissions.org,
        'org.mail-profiles.index',
    ) || canOrg(permissions.org, 'org.member-mail-linkage.show');
    const activeHref = NotificationPreferenceController.show.url(
        organization.id,
    );

    return (
        <>
            <Head title={`Notification preferences · ${organization.name}`} />
            <SettingsShell
                title="Notification preferences"
                description={`Choose how you receive updates in ${organization.name}.`}
                backHref={OrganizationController.show.url(organization.id)}
                nav={buildOrganizationSettingsNav(
                    organization.id,
                    permissions,
                    activeHref,
                )}
            >
                {canViewMail && (
                    <p className="text-sm text-muted-foreground">
                        SMTP and personal Gmail settings live on the{' '}
                        <Link
                            href={OrganizationMailProfileController.index.url(
                                organization.id,
                            )}
                            className="text-primary underline-offset-4 hover:underline"
                        >
                            Mail
                        </Link>{' '}
                        page.
                    </p>
                )}

                <CommandCard
                    title="Channels by event"
                    description="Toggle in-app and email notifications for each event type."
                    dot="blue"
                >
                    {canUpdate ? (
                        <Form
                            {...NotificationPreferenceController.update.form(
                                organization.id,
                            )}
                            className="flex flex-col gap-4"
                        >
                            {({ processing }) => (
                                <>
                                    <div className="overflow-x-auto">
                                        <table className="w-full min-w-[32rem] text-sm">
                                            <thead>
                                                <tr className="border-b border-border/60 text-left">
                                                    <th className="py-2 pr-4 font-medium">
                                                        Event
                                                    </th>
                                                    <th className="px-4 py-2 font-medium">
                                                        In-app
                                                    </th>
                                                    <th className="py-2 pl-4 font-medium">
                                                        Email
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {matrix.map((row, rowIndex) => {
                                                    const databaseIndex =
                                                        rowIndex * 2;
                                                    const mailIndex =
                                                        rowIndex * 2 + 1;

                                                    return (
                                                        <tr
                                                            key={row.event_type}
                                                            className="border-b border-border/50 last:border-0"
                                                        >
                                                            <td className="py-3 pr-4 align-middle">
                                                                {row.label}
                                                                <input
                                                                    type="hidden"
                                                                    name={`preferences[${databaseIndex}][event_type]`}
                                                                    value={
                                                                        row.event_type
                                                                    }
                                                                />
                                                                <input
                                                                    type="hidden"
                                                                    name={`preferences[${mailIndex}][event_type]`}
                                                                    value={
                                                                        row.event_type
                                                                    }
                                                                />
                                                            </td>
                                                            <td className="px-4 py-3 align-middle">
                                                                <input
                                                                    type="hidden"
                                                                    name={`preferences[${databaseIndex}][channel]`}
                                                                    value="database"
                                                                />
                                                                <input
                                                                    type="hidden"
                                                                    name={`preferences[${databaseIndex}][is_enabled]`}
                                                                    value="0"
                                                                />
                                                                <input
                                                                    type="checkbox"
                                                                    name={`preferences[${databaseIndex}][is_enabled]`}
                                                                    value="1"
                                                                    defaultChecked={
                                                                        row
                                                                            .channels
                                                                            .database
                                                                    }
                                                                />
                                                            </td>
                                                            <td className="py-3 pl-4 align-middle">
                                                                <input
                                                                    type="hidden"
                                                                    name={`preferences[${mailIndex}][channel]`}
                                                                    value="mail"
                                                                />
                                                                <input
                                                                    type="hidden"
                                                                    name={`preferences[${mailIndex}][is_enabled]`}
                                                                    value="0"
                                                                />
                                                                <input
                                                                    type="checkbox"
                                                                    name={`preferences[${mailIndex}][is_enabled]`}
                                                                    value="1"
                                                                    defaultChecked={
                                                                        row
                                                                            .channels
                                                                            .mail
                                                                    }
                                                                />
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                    <SubmitButton processing={processing}>
                                        Save preferences
                                    </SubmitButton>
                                </>
                            )}
                        </Form>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            You can view but not edit notification preferences.
                        </p>
                    )}
                </CommandCard>
            </SettingsShell>
        </>
    );
}
