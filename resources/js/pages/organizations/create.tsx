import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import { Form, Head, Link } from '@inertiajs/react';
import { CommandCard } from '@/components/command-centre/command-card';
import { PageShell } from '@/components/command-centre/page-shell';
import InputError from '@/components/input-error';
import { SubmitButton } from '@/components/submit-button';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/organizations';

type OrganizationsCreateProps = {
    defaultTimezone: string;
};

export default function OrganizationsCreate({
    defaultTimezone,
}: OrganizationsCreateProps) {
    return (
        <>
            <Head title="Create organization" />
            <PageShell
                title="Create organization"
                subtitle="Set up a new workspace for your command centre."
            >
                <CommandCard title="Workspace details" dot="crimson" className="max-w-xl">
                    <Form
                        {...OrganizationController.store.form()}
                        className="grid gap-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Organization name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        autoFocus
                                        placeholder="TCM Group"
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="settings.timezone">
                                        Timezone
                                    </Label>
                                    <Input
                                        id="settings.timezone"
                                        name="settings[timezone]"
                                        defaultValue={defaultTimezone}
                                    />
                                    <InputError
                                        message={errors['settings.timezone']}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="settings.focus_cap">
                                        Daily focus cap
                                    </Label>
                                    <Input
                                        id="settings.focus_cap"
                                        name="settings[focus_cap]"
                                        type="number"
                                        min={1}
                                        max={10}
                                        defaultValue={10}
                                    />
                                    <InputError
                                        message={errors['settings.focus_cap']}
                                    />
                                </div>
                                <div className="flex items-center gap-3">
                                    <SubmitButton processing={processing}>
                                        Create organization
                                    </SubmitButton>
                                    <Button variant="outline" asChild>
                                        <Link href={index.url()}>Cancel</Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </CommandCard>
            </PageShell>
        </>
    );
}
