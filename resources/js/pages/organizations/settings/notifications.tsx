import MemberMailLinkageController from '@/actions/App/Http/Controllers/MemberMailLinkageController';
import NotificationPreferenceController from '@/actions/App/Http/Controllers/NotificationPreferenceController';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import { Form, Head, Link, router } from '@inertiajs/react';
import { Mail, Send } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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

type PreferenceMatrixRow = {
    event_type: string;
    label: string;
    channels: {
        database: boolean;
        mail: boolean;
    };
};

type MailLinkageSummary = {
    gmail_address: string | null;
    is_verified: boolean;
    last_tested_at: string | null;
    has_app_pin: boolean;
};

type NotificationPreferencesProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    matrix: PreferenceMatrixRow[];
    mailLinkage: MailLinkageSummary;
    permissions: CommandCentrePermissions;
};

export default function OrganizationNotificationPreferences({
    organization,
    matrix,
    mailLinkage,
    permissions,
}: NotificationPreferencesProps) {
    const canUpdate = canOrg(
        permissions.org,
        'org.notification-preferences.update',
    );
    const canUpdateMailLinkage = canOrg(
        permissions.org,
        'org.member-mail-linkage.update',
    );
    const canTestMailLinkage = canOrg(
        permissions.org,
        'org.member-mail-linkage.test',
    );

    return (
        <>
            <Head title={`Notification preferences · ${organization.name}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
                <Heading
                    title="Notification preferences"
                    description={`Choose how you receive updates in ${organization.name}.`}
                />

                <Link
                    href={OrganizationController.show.url(organization.id)}
                    className="text-sm underline underline-offset-4"
                >
                    Back to settings
                </Link>

                {canUpdateMailLinkage && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Mail className="size-4" />
                                Personal Gmail linkage
                            </CardTitle>
                            <CardDescription>
                                Connect your Gmail address and Google app pin
                                to send member invitations from your account.
                                Generate an app pin in Google Account →
                                Security → App passwords.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-wrap items-center gap-2">
                                {mailLinkage.is_verified ? (
                                    <Badge variant="secondary">Verified</Badge>
                                ) : (
                                    <Badge variant="outline">
                                        Verification required
                                    </Badge>
                                )}
                                {mailLinkage.gmail_address && (
                                    <span className="text-sm text-muted-foreground">
                                        {mailLinkage.gmail_address}
                                    </span>
                                )}
                            </div>
                            <Form
                                {...MemberMailLinkageController.update.form(
                                    organization.id,
                                )}
                                className="grid gap-4 sm:grid-cols-2"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="gmail_address">
                                                Gmail address
                                            </Label>
                                            <Input
                                                id="gmail_address"
                                                name="gmail_address"
                                                type="email"
                                                defaultValue={
                                                    mailLinkage.gmail_address ??
                                                    ''
                                                }
                                                placeholder="you@gmail.com"
                                                required
                                            />
                                            <InputError
                                                message={errors.gmail_address}
                                            />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="app_pin">
                                                Google app pin
                                            </Label>
                                            <Input
                                                id="app_pin"
                                                name="app_pin"
                                                type="password"
                                                placeholder={
                                                    mailLinkage.has_app_pin
                                                        ? 'Enter a new pin to replace saved pin'
                                                        : '16-character app password'
                                                }
                                                required={
                                                    !mailLinkage.has_app_pin
                                                }
                                                autoComplete="off"
                                            />
                                            <InputError
                                                message={errors.app_pin}
                                            />
                                            <InputError
                                                message={errors.mail_linkage}
                                            />
                                        </div>
                                        <div className="flex flex-wrap gap-2 sm:col-span-2">
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Save Gmail linkage
                                            </Button>
                                            {canTestMailLinkage &&
                                                mailLinkage.gmail_address && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={() => {
                                                            router.post(
                                                                MemberMailLinkageController.test.url(
                                                                    organization.id,
                                                                ),
                                                            );
                                                        }}
                                                    >
                                                        <Send className="size-4" />
                                                        Test send
                                                    </Button>
                                                )}
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Channels by event</CardTitle>
                        <CardDescription>
                            Toggle in-app and email notifications for each
                            event type.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
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
                                                    <tr className="border-b text-left">
                                                        <th className="py-2 pr-4 font-medium">
                                                            Event
                                                        </th>
                                                        <th className="py-2 px-4 font-medium">
                                                            In-app
                                                        </th>
                                                        <th className="py-2 pl-4 font-medium">
                                                            Email
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {matrix.map(
                                                        (row, rowIndex) => {
                                                            const databaseIndex =
                                                                rowIndex * 2;
                                                            const mailIndex =
                                                                rowIndex * 2 +
                                                                1;

                                                            return (
                                                            <tr
                                                                key={
                                                                    row.event_type
                                                                }
                                                                className="border-b last:border-0"
                                                            >
                                                                <td className="py-3 pr-4 align-middle">
                                                                    {
                                                                        row.label
                                                                    }
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
                                                        },
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Save preferences
                                        </Button>
                                    </>
                                )}
                            </Form>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                You can view but not edit notification
                                preferences.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
