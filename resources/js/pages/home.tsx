import { Head } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Heading from '@/components/heading';

export default function Home() {
    return (
        <>
            <Head title="Home" />
            <div className="flex flex-col gap-6 p-6">
                <Heading
                    title="Command Centre"
                    description="Organization workspace and executive dashboard modules are being built next."
                />

                <Card className="max-w-2xl">
                    <CardHeader className="flex flex-row items-center gap-3 space-y-0">
                        <div className="flex size-10 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <Building2 className="size-5" aria-hidden />
                        </div>
                        <CardTitle>Next up</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-muted-foreground">
                        <p>
                            Milestone 1 adds organization home, org switching,
                            and the content header organization selector.
                        </p>
                        <p>
                            See{' '}
                            <code className="text-foreground">
                                DOCs/TCM-Command-Centre-Implementation-Guide.md
                            </code>{' '}
                            for the full build order.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
