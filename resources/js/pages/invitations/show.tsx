import InvitationAcceptController from '@/actions/App/Http/Controllers/InvitationAcceptController';
import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { login } from '@/routes';

type InvitationShowProps = {
    token: string;
    invitation: {
        id: number;
        email: string;
        organization_name: string;
        role_name: string;
        expires_at: string;
    };
    canAccept: boolean;
};

export default function InvitationShow({
    token,
    invitation,
    canAccept,
}: InvitationShowProps) {
    return (
        <>
            <Head title="Organization invitation" />
            <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-6">
                <Heading
                    title="You are invited"
                    description={`Join ${invitation.organization_name} as ${invitation.role_name}.`}
                />

                <Card>
                    <CardHeader>
                        <CardTitle>{invitation.organization_name}</CardTitle>
                        <CardDescription>
                            Invitation sent to {invitation.email}. Expires{' '}
                            {new Date(invitation.expires_at).toLocaleString()}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3">
                        {canAccept ? (
                            <Form
                                {...InvitationAcceptController.accept.form({
                                    token,
                                })}
                            >
                                <Button type="submit">Accept invitation</Button>
                            </Form>
                        ) : (
                            <>
                                <p className="text-sm text-muted-foreground">
                                    Sign in with {invitation.email} to accept
                                    this invitation.
                                </p>
                                <Button asChild variant="outline">
                                    <Link href={login.url()}>Sign in</Link>
                                </Button>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
