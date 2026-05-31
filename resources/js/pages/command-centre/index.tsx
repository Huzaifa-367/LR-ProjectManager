import MemberDailyFocusController from '@/actions/App/Http/Controllers/CommandCentre/MemberDailyFocusController';
import MemberNoteController from '@/actions/App/Http/Controllers/CommandCentre/MemberNoteController';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Head, Link, router } from '@inertiajs/react';
import { Check, Pin, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
import { canOrg } from '@/hooks/use-org-permissions';
import type { CommandCentrePageProps } from '@/types/command-centre';

export default function CommandCentreIndex(props: CommandCentrePageProps) {
    const {
        organization,
        currentMember,
        permissions,
        stats,
        focusPins,
        tasks,
        reminders,
        projects,
        notes,
        assignedToMe,
        members,
        focusCap,
        filters,
    } = props;

    const [noteBody, setNoteBody] = useState('');

    const activeFocusCount = focusPins.filter((pin) => !pin.task.is_done).length;

    return (
        <>
            <Head title="Command Centre" />
            <div className="flex flex-col gap-6 p-4 pb-20 sm:p-6">
                <section className="flex flex-col gap-4 border-b pb-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Good day,{' '}
                            <span className="text-primary">
                                {currentMember.display_name}
                            </span>
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {organization.name} command centre
                        </p>
                    </div>
                    <dl className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <Stat label="Priorities" value={stats.active_focus} />
                        <Stat label="Open tasks" value={stats.open_tasks} />
                        <Stat label="Projects" value={stats.projects} />
                        <Stat
                            label="Done today"
                            value={stats.done_today}
                            accent
                        />
                    </dl>
                </section>

                <div className="grid gap-6 xl:grid-cols-[minmax(280px,380px)_1fr]">
                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Assigned to me</CardTitle>
                                <CardDescription>
                                    Open tasks where you are an assignee.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {assignedToMe.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Nothing assigned right now.
                                    </p>
                                ) : (
                                    assignedToMe.map((task) => (
                                        <TaskRow
                                            key={task.id}
                                            organizationId={organization.id}
                                            task={task}
                                            permissions={permissions.org}
                                        />
                                    ))
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex-row items-center justify-between space-y-0">
                                <div>
                                    <CardTitle>Today&apos;s priorities</CardTitle>
                                    <CardDescription>
                                        {activeFocusCount}/{focusCap} focus
                                        pins
                                    </CardDescription>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {focusPins.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Pin tasks from your list or set a
                                        deadline to today.
                                    </p>
                                ) : (
                                    focusPins.map((pin, index) => (
                                        <div
                                            key={pin.id}
                                            className="flex items-start gap-2 rounded-md border p-2"
                                        >
                                            <span className="mt-0.5 text-xs font-semibold text-muted-foreground">
                                                {index + 1}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <p
                                                    className={`text-sm font-medium ${pin.task.is_done ? 'text-muted-foreground line-through' : ''}`}
                                                >
                                                    {pin.task.title}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {pin.task.project_name}
                                                    {pin.is_auto
                                                        ? ' · auto'
                                                        : ''}
                                                </p>
                                            </div>
                                            <div className="flex gap-1">
                                                {canOrg(
                                                    permissions.org,
                                                    'org.tasks.toggle-done',
                                                ) && (
                                                    <Button
                                                        size="icon"
                                                        variant="ghost"
                                                        className="size-7"
                                                        onClick={() => {
                                                            router.patch(
                                                                TaskController.toggleDone.url(
                                                                    [
                                                                        organization.id,
                                                                        pin.task
                                                                            .id,
                                                                    ],
                                                                ),
                                                            );
                                                        }}
                                                    >
                                                        <Check className="size-4" />
                                                    </Button>
                                                )}
                                                {canOrg(
                                                    permissions.org,
                                                    'org.focus.destroy',
                                                ) && (
                                                    <Button
                                                        size="icon"
                                                        variant="ghost"
                                                        className="size-7"
                                                        onClick={() => {
                                                            router.delete(
                                                                MemberDailyFocusController.destroy.url(
                                                                    [
                                                                        organization.id,
                                                                        pin.id,
                                                                    ],
                                                                ),
                                                            );
                                                        }}
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Reminders</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {reminders.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No reminders due.
                                    </p>
                                ) : (
                                    reminders.map((reminder) => (
                                        <div
                                            key={reminder.id}
                                            className="rounded-md border p-2 text-sm"
                                        >
                                            {reminder.title}
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Notes</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {notes.map((note) => (
                                    <div
                                        key={note.id}
                                        className="rounded-md border p-2 text-sm"
                                    >
                                        {note.body}
                                    </div>
                                ))}
                                {canOrg(
                                    permissions.org,
                                    'org.notes.store',
                                ) && (
                                    <form
                                        className="flex gap-2"
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            router.post(
                                                MemberNoteController.store.url(
                                                    organization.id,
                                                ),
                                                { body: noteBody },
                                                {
                                                    onSuccess: () =>
                                                        setNoteBody(''),
                                                },
                                            );
                                        }}
                                    >
                                        <Input
                                            value={noteBody}
                                            onChange={(event) =>
                                                setNoteBody(
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Add a note…"
                                        />
                                        <Button type="submit" size="icon">
                                            <Plus className="size-4" />
                                        </Button>
                                    </form>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Tasks</CardTitle>
                                <CardDescription>
                                    Filter by teammate. Use the project
                                    selector in the header to narrow by
                                    project.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex flex-wrap gap-2">
                                    <FilterButton
                                        label="Everyone"
                                        active={
                                            filters.assignee_member_id === null
                                        }
                                        onClick={() =>
                                            applyFilters(organization.id, {
                                                assignee_member_id: null,
                                            })
                                        }
                                    />
                                    {members.map((member) => (
                                        <FilterButton
                                            key={member.id}
                                            label={member.display_name}
                                            active={
                                                filters.assignee_member_id ===
                                                member.id
                                            }
                                            onClick={() =>
                                                applyFilters(organization.id, {
                                                    assignee_member_id:
                                                        member.id,
                                                })
                                            }
                                        />
                                    ))}
                                </div>
                                <div className="space-y-2">
                                    {tasks.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            No tasks in this view.
                                        </p>
                                    ) : (
                                        tasks.map((task) => (
                                            <div
                                                key={task.id}
                                                className="flex items-center gap-2 rounded-md border p-2"
                                            >
                                                <div className="min-w-0 flex-1">
                                                    <TaskRow
                                                        organizationId={
                                                            organization.id
                                                        }
                                                        task={task}
                                                        permissions={
                                                            permissions.org
                                                        }
                                                    />
                                                </div>
                                                {canOrg(
                                                    permissions.org,
                                                    'org.focus.store',
                                                ) &&
                                                    !task.is_done && (
                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            className="size-7 shrink-0"
                                                            onClick={() => {
                                                                router.post(
                                                                    MemberDailyFocusController.store.url(
                                                                        organization.id,
                                                                    ),
                                                                    {
                                                                        task_id:
                                                                            task.id,
                                                                        focus_date:
                                                                            filters.focus_date,
                                                                    },
                                                                );
                                                            }}
                                                        >
                                                            <Pin className="size-4" />
                                                        </Button>
                                                    )}
                                            </div>
                                        ))
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Strategic projects</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {projects.map((project) => (
                                        <Link
                                            key={project.id}
                                            href={`/organizations/${organization.id}/projects/${project.id}`}
                                            className="rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                        >
                                            <p className="font-medium">
                                                {project.name}
                                            </p>
                                            <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                                {project.objective}
                                            </p>
                                            <div className="mt-2 flex gap-2">
                                                <Badge variant="outline">
                                                    {project.health}
                                                </Badge>
                                                <Badge variant="secondary">
                                                    {project.progress_percent}%
                                                </Badge>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <footer className="fixed inset-x-0 bottom-0 z-10 border-t bg-background/95 px-4 py-2 backdrop-blur sm:px-6">
                    <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                        <span>{stats.open_tasks} open tasks</span>
                        <span>{stats.done_today} done today</span>
                        <span>{stats.projects} active projects</span>
                        <span>Saved</span>
                    </div>
                </footer>
            </div>
        </>
    );
}

function Stat({
    label,
    value,
    accent = false,
}: {
    label: string;
    value: number;
    accent?: boolean;
}) {
    return (
        <div className="text-right">
            <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">
                {label}
            </dt>
            <dd
                className={`text-xl font-semibold ${accent ? 'text-green-600 dark:text-green-400' : ''}`}
            >
                {value}
            </dd>
        </div>
    );
}

function FilterButton({
    label,
    active,
    onClick,
}: {
    label: string;
    active: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-full border px-2.5 py-0.5 text-xs transition-colors ${
                active
                    ? 'border-primary/30 bg-primary/10 text-primary'
                    : 'text-muted-foreground hover:bg-muted'
            }`}
        >
            {label}
        </button>
    );
}

function TaskRow({
    organizationId,
    task,
    permissions,
}: {
    organizationId: number;
    task: CommandCentrePageProps['tasks'][number];
    permissions: string[];
}) {
    return (
        <div className="flex items-center justify-between gap-2 text-sm">
            <div className="min-w-0">
                <Link
                    href={TaskController.show.url([organizationId, task.id])}
                    className={`font-medium hover:underline ${task.is_done ? 'text-muted-foreground line-through' : ''}`}
                >
                    {task.title}
                </Link>
                <p className="text-xs text-muted-foreground">
                    {task.project_name}
                </p>
            </div>
            <Badge variant="outline">{task.status}</Badge>
            {canOrg(permissions, 'org.tasks.toggle-done') && !task.is_done && (
                <Button
                    size="sm"
                    variant="ghost"
                    onClick={() => {
                        router.patch(
                            TaskController.toggleDone.url([
                                organizationId,
                                task.id,
                            ]),
                        );
                    }}
                >
                    Done
                </Button>
            )}
        </div>
    );
}

function applyFilters(
    organizationId: number,
    next: { assignee_member_id?: number | null },
): void {
    const params = new URLSearchParams(window.location.search);

    if ('assignee_member_id' in next) {
        if (next.assignee_member_id === null) {
            params.delete('assignee_member_id');
        } else {
            params.set(
                'assignee_member_id',
                String(next.assignee_member_id),
            );
        }
    }

    const query = params.toString();
    router.get(
        `/organizations/${organizationId}/command-centre${query ? `?${query}` : ''}`,
        {},
        { preserveState: true, preserveScroll: true },
    );
}
