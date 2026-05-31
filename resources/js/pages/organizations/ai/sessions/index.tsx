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

type AiSessionListItem = {
    id: number;
    context: string;
    title: string | null;
    status: string;
};

type AiSessionsIndexProps = {
    organization: OrganizationSummary;
    sessions: AiSessionListItem[];
    permissions: CommandCentrePermissions;
};

export default function AiSessionsIndex({
    organization,
    sessions,
}: AiSessionsIndexProps) {
    return (
        <>
            <Head title="AI sessions" />
            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title="AI sessions"
                    description={`Recent sessions for ${organization.name}.`}
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Sessions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {sessions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No AI sessions yet.
                            </p>
                        ) : (
                            sessions.map((session) => (
                                <div
                                    key={session.id}
                                    className="rounded-md border px-3 py-2 text-sm"
                                >
                                    <div className="font-medium">
                                        {session.title ?? session.context}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {session.status}
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
