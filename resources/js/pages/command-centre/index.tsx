import MemberDailyFocusController from '@/actions/App/Http/Controllers/CommandCentre/MemberDailyFocusController';
import ProjectOnboardingController from '@/actions/App/Http/Controllers/CommandCentre/ProjectOnboardingController';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { CommandCard } from '@/components/command-centre/command-card';
import { CreateNoteDialog } from '@/components/command-centre/create-note-dialog';
import { CreateTaskDialog } from '@/components/command-centre/create-task-dialog';
import { EmptyState } from '@/components/command-centre/empty-state';
import { FilterPill } from '@/components/command-centre/filter-pill';
import { PageShell } from '@/components/command-centre/page-shell';
import { StatusPill } from '@/components/command-centre/status-pill';
import { TaskKindBadge } from '@/components/command-centre/task-kind-badge';
import { TaskMetaLine } from '@/components/command-centre/task-deadline-label';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
import type { CommandCentrePageProps } from '@/types/command-centre';
import { Head, Link, router } from '@inertiajs/react';
import { Check, Pin, Sparkles, Trash2 } from 'lucide-react';
import { useOrganizationContext } from '@/hooks/use-organization-context';

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

    const activeFocusCount = focusPins.filter((pin) => !pin.task.is_done).length;
    const projectOptions = projects.map((project) => ({
        id: project.id,
        name: project.name,
    }));
    const canCreateTask = canOrg(permissions.org, 'org.tasks.store');
    const canCreateNote = canOrg(permissions.org, 'org.notes.store');
    const { aiEnabled } = useOrganizationContext();
    const canStartOnboarding =
        aiEnabled && canOrg(permissions.org, 'org.ai-onboarding.start');

    return (
        <>
            <Head title="Command Centre" />
            <PageShell
                title="Good day,"
                accent={currentMember.display_name}
                subtitle={organization.name}
                stats={[
                    { label: 'Priorities', value: stats.active_focus },
                    { label: 'Open tasks', value: stats.open_tasks },
                    { label: 'Projects', value: stats.projects },
                    {
                        label: 'Done today',
                        value: stats.done_today,
                        tone: 'success',
                    },
                ]}
                actions={
                    canStartOnboarding ? (
                        <Button asChild variant="outline" size="sm">
                            <Link
                                href={ProjectOnboardingController.create.url(
                                    organization.id,
                                )}
                            >
                                <Sparkles className="size-4" />
                                AI onboarding
                            </Link>
                        </Button>
                    ) : undefined
                }
            >
                <div className="grid gap-[18px] xl:grid-cols-[380px_1fr]">
                    <div className="flex flex-col gap-[18px]">
                        <CommandCard
                            title="Assigned to me"
                            description="Open tasks where you are an assignee."
                            dot="blue"
                        >
                            {assignedToMe.length === 0 ? (
                                <EmptyState>
                                    Nothing assigned right now.
                                </EmptyState>
                            ) : (
                                <ul className="divide-y divide-border/50">
                                    {assignedToMe.map((task) => (
                                        <li
                                            key={task.id}
                                            className="tcm-list-row"
                                        >
                                            <TaskRow
                                                organizationId={
                                                    organization.id
                                                }
                                                task={task}
                                                permissions={permissions.org}
                                            />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CommandCard>

                        <CommandCard
                            title="Today's priorities"
                            description={`${activeFocusCount}/${focusCap} focus pins`}
                            dot="crimson"
                        >
                            {focusPins.length === 0 ? (
                                <EmptyState>
                                    Pin tasks from your list or set a deadline
                                    to today.
                                </EmptyState>
                            ) : (
                                <ul className="divide-y divide-border/50">
                                    {focusPins.map((pin, index) => (
                                        <li
                                            key={pin.id}
                                            className="tcm-list-row"
                                        >
                                            <span className="mt-0.5 w-4 shrink-0 text-xs font-semibold text-muted-foreground">
                                                {index + 1}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <p
                                                    className={`text-sm font-medium ${pin.task.is_done ? 'text-muted-foreground line-through' : ''}`}
                                                >
                                                    {pin.task.title}
                                                </p>
                                                <TaskMetaLine
                                                    projectName={
                                                        pin.task.project_name
                                                    }
                                                    deadlineDate={
                                                        pin.task.deadline_date
                                                    }
                                                    deadlineUi={
                                                        pin.task.deadline_ui
                                                    }
                                                    suffix={
                                                        pin.is_auto
                                                            ? 'auto'
                                                            : null
                                                    }
                                                />
                                            </div>
                                            <div className="flex shrink-0 gap-1">
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
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CommandCard>

                        <CommandCard title="Reminders" dot="gold">
                            {reminders.length === 0 ? (
                                <EmptyState>No reminders due.</EmptyState>
                            ) : (
                                <ul className="divide-y divide-border/50">
                                    {reminders.map((reminder) => (
                                        <li
                                            key={reminder.id}
                                            className="tcm-list-row"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <Link
                                                    href={TaskController.show.url(
                                                        [
                                                            organization.id,
                                                            reminder.id,
                                                        ],
                                                    )}
                                                    className="text-sm font-medium hover:text-primary hover:underline"
                                                >
                                                    {reminder.title}
                                                </Link>
                                                <TaskMetaLine
                                                    projectName={
                                                        reminder.project_name
                                                    }
                                                    deadlineDate={
                                                        reminder.deadline_date
                                                    }
                                                    deadlineUi={
                                                        reminder.deadline_ui
                                                    }
                                                />
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CommandCard>

                        <CommandCard
                            title="Notes"
                            dot="green"
                            action={
                                canCreateNote ? (
                                    <CreateNoteDialog
                                        organizationId={organization.id}
                                    />
                                ) : undefined
                            }
                        >
                            {notes.length === 0 ? (
                                <EmptyState>No notes yet.</EmptyState>
                            ) : (
                                <ul className="divide-y divide-border/50">
                                    {notes.map((note) => (
                                        <li
                                            key={note.id}
                                            className="tcm-list-row text-sm"
                                        >
                                            {note.body}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CommandCard>
                    </div>

                    <div className="flex flex-col gap-[18px]">
                        <CommandCard
                            title="Tasks"
                            description="Filter by teammate. Use the project selector in the header to narrow by project."
                            dot="crimson"
                            action={
                                canCreateTask &&
                                projectOptions.length > 0 ? (
                                    <CreateTaskDialog
                                        organizationId={organization.id}
                                        projects={projectOptions}
                                    />
                                ) : undefined
                            }
                        >
                            <div className="mb-3 flex flex-wrap gap-2">
                                <FilterPill
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
                                    <FilterPill
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
                            {tasks.length === 0 ? (
                                <EmptyState>
                                    No tasks in this view.
                                </EmptyState>
                            ) : (
                                <ul className="divide-y divide-border/50">
                                    {tasks.map((task) => (
                                        <li
                                            key={task.id}
                                            className="tcm-list-row"
                                        >
                                            <TaskRow
                                                organizationId={
                                                    organization.id
                                                }
                                                task={task}
                                                permissions={permissions.org}
                                            />
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
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CommandCard>

                        <CommandCard
                            title="Strategic projects"
                            dot="gold"
                        >
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {projects.map((project) => (
                                    <Link
                                        key={project.id}
                                        href={`/organizations/${organization.id}/projects/${project.id}`}
                                        className="rounded-lg border border-border/70 p-3 transition-colors hover:border-border hover:bg-muted/30"
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
                        </CommandCard>
                    </div>
                </div>
            </PageShell>

            <footer className="fixed inset-x-0 bottom-0 z-10 border-t bg-background/95 px-4 py-2 backdrop-blur sm:px-6">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                    <span>{stats.open_tasks} open tasks</span>
                    <span>{stats.done_today} done today</span>
                    <span>{stats.projects} active projects</span>
                    <span>Saved</span>
                </div>
            </footer>
        </>
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
        <>
            <div className="min-w-0 flex-1">
                <div className="mb-1 flex flex-wrap items-center gap-2">
                    <TaskKindBadge kind={task.kind} />
                    <Link
                        href={TaskController.show.url([organizationId, task.id])}
                        className={`truncate text-sm font-medium hover:text-primary hover:underline ${task.is_done ? 'text-muted-foreground line-through' : ''}`}
                    >
                        {task.title}
                    </Link>
                </div>
                <TaskMetaLine
                    projectName={task.project_name}
                    deadlineDate={task.deadline_date}
                    deadlineUi={task.deadline_ui}
                />
            </div>
            <StatusPill status={task.status} />
            {canOrg(permissions, 'org.tasks.toggle-done') && !task.is_done && (
                <Button
                    size="sm"
                    variant="ghost"
                    className="shrink-0"
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
        </>
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
