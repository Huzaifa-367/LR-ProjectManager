import MemberMailLinkageController from '@/actions/App/Http/Controllers/MemberMailLinkageController';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import OrganizationMailProfileController from '@/actions/App/Http/Controllers/OrganizationMailProfileController';
import { Form, Head, router } from '@inertiajs/react';
import { Mail, Send } from 'lucide-react';
import { CommandCard } from '@/components/command-centre/command-card';
import { EmptyState } from '@/components/command-centre/empty-state';
import { SettingsShell } from '@/components/command-centre/settings-shell';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { canOrg } from '@/hooks/use-org-permissions';
import { buildOrganizationSettingsNav } from '@/lib/organization-settings-nav';
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

type MailLinkageSummary = {
    gmail_address: string | null;
    is_verified: boolean;
    last_tested_at: string | null;
    has_app_pin: boolean;
};

type MailSettingsProps = {
    organization: Pick<OrganizationSummary, 'id' | 'name'>;
    profiles: MailProfileRow[];
    mailLinkage: MailLinkageSummary;
    permissions: CommandCentrePermissions;
};

export default function OrganizationMailSettings({
    organization,
    profiles,
    mailLinkage,
    permissions,
}: MailSettingsProps) {
    const canViewSmtp = canOrg(permissions.org, 'org.mail-profiles.index');
    const canStore = canOrg(permissions.org, 'org.mail-profiles.store');
    const canUpdate = canOrg(permissions.org, 'org.mail-profiles.update');
    const canTest = canOrg(permissions.org, 'org.mail-profiles.test');
    const canDestroy = canOrg(permissions.org, 'org.mail-profiles.destroy');
    const canShowPersonal = canOrg(
        permissions.org,
        'org.member-mail-linkage.show',
    );
    const canUpdatePersonal = canOrg(
        permissions.org,
        'org.member-mail-linkage.update',
    );
    const canTestPersonal = canOrg(
        permissions.org,
        'org.member-mail-linkage.test',
    );
    const activeHref = OrganizationMailProfileController.index.url(
        organization.id,
    );

    return (
        <>
            <Head title={`Mail · ${organization.name}`} />
            <SettingsShell
                title="Mail"
                description={`Organization SMTP and personal Gmail settings for ${organization.name}.`}
                backHref={OrganizationController.show.url(organization.id)}
                nav={buildOrganizationSettingsNav(
                    organization.id,
                    permissions,
                    activeHref,
                )}
            >
                {canViewSmtp && (
                    <>
                        {canStore && (
                            <CommandCard
                                title="Add SMTP profile"
                                description="Configure outbound email for organization notifications and invitations."
                                dot="crimson"
                            >
                                <Form
                                    {...OrganizationMailProfileController.store.form(
                                        organization.id,
                                    )}
                                    className="grid gap-4 sm:grid-cols-2"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid gap-2 sm:col-span-2">
                                                <Label htmlFor="smtp-name">
                                                    Profile name
                                                </Label>
                                                <Input
                                                    id="smtp-name"
                                                    name="name"
                                                    placeholder="TCM Alerts"
                                                    required
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>
                                            <input
                                                type="hidden"
                                                name="provider"
                                                value="smtp"
                                            />
                                            <div className="grid gap-2">
                                                <Label htmlFor="smtp-from-name">
                                                    From name
                                                </Label>
                                                <Input
                                                    id="smtp-from-name"
                                                    name="from_name"
                                                    required
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="smtp-from-address">
                                                    From address
                                                </Label>
                                                <Input
                                                    id="smtp-from-address"
                                                    name="from_address"
                                                    type="email"
                                                    required
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="smtp-host">
                                                    SMTP host
                                                </Label>
                                                <Input
                                                    id="smtp-host"
                                                    name="config[host]"
                                                    placeholder="smtp.example.com"
                                                    required
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="smtp-port">
                                                    SMTP port
                                                </Label>
                                                <Input
                                                    id="smtp-port"
                                                    name="config[port]"
                                                    type="number"
                                                    defaultValue={587}
                                                    required
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="smtp-encryption">
                                                    Encryption
                                                </Label>
                                                <select
                                                    id="smtp-encryption"
                                                    name="config[encryption]"
                                                    defaultValue="tls"
                                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                                >
                                                    <option value="tls">
                                                        TLS
                                                    </option>
                                                    <option value="ssl">
                                                        SSL
                                                    </option>
                                                </select>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="smtp-username">
                                                    Username
                                                </Label>
                                                <Input
                                                    id="smtp-username"
                                                    name="config[username]"
                                                    required
                                                />
                                            </div>
                                            <div className="grid gap-2 sm:col-span-2">
                                                <Label htmlFor="smtp-password">
                                                    Password
                                                </Label>
                                                <Input
                                                    id="smtp-password"
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
                            </CommandCard>
                        )}

                        <CommandCard
                            title="Organization SMTP profiles"
                            dot="blue"
                            badge={
                                profiles.length > 0 ? (
                                    <Badge variant="secondary">
                                        {profiles.length}
                                    </Badge>
                                ) : undefined
                            }
                        >
                            {profiles.length === 0 ? (
                                <EmptyState>
                                    No SMTP profiles configured yet.
                                </EmptyState>
                            ) : (
                                <ul className="divide-y divide-border/50">
                                    {profiles.map((profile) => (
                                        <li
                                            key={profile.id}
                                            className="tcm-list-row items-center justify-between"
                                        >
                                            <div>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="text-sm font-medium">
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
                                                <p className="text-xs text-muted-foreground">
                                                    {profile.from_name} &lt;
                                                    {profile.from_address}&gt; ·{' '}
                                                    {profile.provider}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {canUpdate &&
                                                    !profile.is_default && (
                                                        <Button
                                                            variant="secondary"
                                                            size="sm"
                                                            onClick={() => {
                                                                router.patch(
                                                                    OrganizationMailProfileController.update.url(
                                                                        [
                                                                            organization.id,
                                                                            profile.id,
                                                                        ],
                                                                    ),
                                                                    {
                                                                        is_default: true,
                                                                    },
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                );
                                                            }}
                                                        >
                                                            Make default
                                                        </Button>
                                                    )}
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
                                                        Test
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
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CommandCard>
                    </>
                )}

                {canShowPersonal && (
                    <CommandCard
                        title="Personal Gmail"
                        description="Connect your Gmail address and Google app password to send member invitations from your account."
                        dot="gold"
                    >
                        <div className="mb-4 flex flex-wrap items-center gap-2">
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
                        {canUpdatePersonal ? (
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
                                                Google app password
                                            </Label>
                                            <Input
                                                id="app_pin"
                                                name="app_pin"
                                                type="password"
                                                placeholder={
                                                    mailLinkage.has_app_pin
                                                        ? 'Enter a new password to replace saved pin'
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
                                                <Mail className="size-4" />
                                                Save Gmail linkage
                                            </Button>
                                            {canTestPersonal &&
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
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                {mailLinkage.gmail_address
                                    ? 'Your personal Gmail linkage is configured. You cannot edit it with your current role.'
                                    : 'Personal Gmail is not connected yet.'}
                            </p>
                        )}
                    </CommandCard>
                )}
            </SettingsShell>
        </>
    );
}
