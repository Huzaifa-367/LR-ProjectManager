import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationMailProfileController from '@/actions/App/Http/Controllers/OrganizationMailProfileController';
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

type MailProfileRow = {
    id: number;
    name: string;
    provider: string;
    is_default: boolean;
    from_name: string;
    from_address: string;
    reply_to_address: string | null;
    is_verified: boolean;
    last_tested_at: string | null;
    is_active: boolean;
};

type MailSettingsProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    profiles: MailProfileRow[];
    permissions: CommandCentrePermissions;
};

export default function OrganizationMailSettings({
    organization,
    profiles,
    permissions,
}: MailSettingsProps) {
    const canStore = canOrg(permissions.org, 'org.mail-profiles.store');
    const canTest = canOrg(permissions.org, 'org.mail-profiles.test');
    const canDestroy = canOrg(permissions.org, 'org.mail-profiles.destroy');

    return (
        <>
            <Head title={`Mail · ${organization.name}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Mail profiles"
                        description={`Outbound email settings for ${organization.name}.`}
                    />
                    <Link
                        href={OrganizationController.show.url(organization.id)}
                        className="text-sm underline underline-offset-4"
                    >
                        Settings
                    </Link>
                </div>

                {canStore && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Mail className="size-4" />
                                Add SMTP profile
                            </CardTitle>
                            <CardDescription>
                                Configure SMTP credentials for organization
                                notifications and invitations.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...OrganizationMailProfileController.store.form(
                                    organization.id,
                                )}
                                className="grid gap-4 sm:grid-cols-2"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="name">Profile name</Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                placeholder="TCM Alerts"
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <input
                                            type="hidden"
                                            name="provider"
                                            value="smtp"
                                        />
                                        <div className="grid gap-2">
                                            <Label htmlFor="from_name">
                                                From name
                                            </Label>
                                            <Input
                                                id="from_name"
                                                name="from_name"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="from_address">
                                                From address
                                            </Label>
                                            <Input
                                                id="from_address"
                                                name="from_address"
                                                type="email"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="config_host">
                                                SMTP host
                                            </Label>
                                            <Input
                                                id="config_host"
                                                name="config[host]"
                                                placeholder="smtp.example.com"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="config_port">
                                                SMTP port
                                            </Label>
                                            <Input
                                                id="config_port"
                                                name="config[port]"
                                                type="number"
                                                defaultValue={587}
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="config_encryption">
                                                Encryption
                                            </Label>
                                            <select
                                                id="config_encryption"
                                                name="config[encryption]"
                                                defaultValue="tls"
                                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                            >
                                                <option value="tls">TLS</option>
                                                <option value="ssl">SSL</option>
                                            </select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="config_username">
                                                Username
                                            </Label>
                                            <Input
                                                id="config_username"
                                                name="config[username]"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="config_password">
                                                Password
                                            </Label>
                                            <Input
                                                id="config_password"
                                                name="config[password]"
                                                type="password"
                                                required
                                            />
                                        </div>
                                        <label className="flex items-center gap-2 text-sm sm:col-span-2">
                                            <input
                                                type="checkbox"
                                                name="is_default"
                                                value="1"
                                                defaultChecked
                                            />
                                            Set as default profile
                                        </label>
                                        <div className="sm:col-span-2">
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Save profile
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Configured profiles</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {profiles.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No mail profiles yet.
                            </p>
                        ) : (
                            profiles.map((profile) => (
                                <div
                                    key={profile.id}
                                    className="flex flex-col gap-3 border-b pb-4 last:border-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">
                                                {profile.name}
                                            </p>
                                            {profile.is_default && (
                                                <Badge>Default</Badge>
                                            )}
                                            {profile.is_verified ? (
                                                <Badge variant="secondary">
                                                    Verified
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline">
                                                    Unverified
                                                </Badge>
                                            )}
                                            {!profile.is_active && (
                                                <Badge variant="outline">
                                                    Inactive
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {profile.from_name} &lt;
                                            {profile.from_address}&gt; ·{' '}
                                            {profile.provider}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {canTest && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    router.post(
                                                        OrganizationMailProfileController.test.url(
                                                            [
                                                                organization.id,
                                                                profile.id,
                                                            ],
                                                        ),
                                                    );
                                                }}
                                            >
                                                <Send className="size-4" />
                                                Test send
                                            </Button>
                                        )}
                                        {canDestroy && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => {
                                                    router.delete(
                                                        OrganizationMailProfileController.destroy.url(
                                                            [
                                                                organization.id,
                                                                profile.id,
                                                            ],
                                                        ),
                                                    );
                                                }}
                                            >
                                                Delete
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
