import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type AiMessageItem = {
    id: number;
    role: string;
    content: string | null;
};

type AiSessionShowProps = {
    organization: OrganizationSummary;
    session: {
        id: number;
        title: string | null;
        status: string;
    };
    messages: AiMessageItem[];
    permissions: CommandCentrePermissions;
};

export default function AiSessionShow({
    organization,
    session,
    messages,
}: AiSessionShowProps) {
    return (
        <>
            <Head title="AI session" />
            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title={session.title ?? 'AI session'}
                    description={`${organization.name} · ${session.status}`}
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Transcript</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {messages.map((message) => (
                            <div
                                key={message.id}
                                className="rounded-md border px-3 py-2 text-sm"
                            >
                                <div className="mb-1 text-xs uppercase text-muted-foreground">
                                    {message.role}
                                </div>
                                <div>{message.content}</div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
