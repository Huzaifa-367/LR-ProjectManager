import MemberDailyFocusController from '@/actions/App/Http/Controllers/CommandCentre/MemberDailyFocusController';
import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import ProjectOnboardingController from '@/actions/App/Http/Controllers/CommandCentre/ProjectOnboardingController';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { CommandCentreSection } from '@/components/command-centre/command-centre-section';
import { CreateNoteDialog } from '@/components/command-centre/create-note-dialog';
import { CreateTaskDialog } from '@/components/command-centre/create-task-dialog';
import { EmptyState } from '@/components/command-centre/empty-state';
import { FilterPill } from '@/components/command-centre/filter-pill';
import { FocusPinRow } from '@/components/command-centre/focus-pin-row';
import { PageShell } from '@/components/command-centre/page-shell';
import { ProjectQuickCard } from '@/components/command-centre/project-quick-card';
import { AssignedToMeList } from '@/components/command-centre/assigned-to-me-list';
import { TaskListRow } from '@/components/command-centre/task-list-row';
import { TaskMetaLine } from '@/components/command-centre/task-deadline-label';
import { WorkspaceToolbar } from '@/components/command-centre/workspace-toolbar';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
import { useOrganizationContext } from '@/hooks/use-organization-context';
import type { CommandCentrePageProps } from '@/types/command-centre';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    Bell,
    CheckSquare,
    FolderKanban,
    Pin,
    Sparkles,
    StickyNote,
} from 'lucide-react';

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
    const canPinFocus = canOrg(permissions.org, 'org.focus.store');
    const canUnpinFocus = canOrg(permissions.org, 'org.focus.destroy');
    const canToggleDone = canOrg(permissions.org, 'org.tasks.toggle-done');
    const { aiEnabled } = useOrganizationContext();
    const canStartOnboarding =
        aiEnabled && canOrg(permissions.org, 'org.ai-onboarding.start');

    const todayLabel = new Intl.DateTimeFormat(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
    }).format(new Date());

    return (
        <>
            <Head title="Command Centre" />
            <PageShell
                title="Good day,"
                accent={currentMember.display_name}
                breadcrumbs={[
                    {
                        title: organization.name,
                        href: `/organizations/${organization.id}/command-centre`,
                    },
                    {
                        title: 'Command Centre',
                        href: `/organizations/${organization.id}/command-centre`,
                    },
                ]}
                stats={[
                    { label: 'Focus today', value: activeFocusCount, tone: 'accent' },
                    { label: 'Open tasks', value: stats.open_tasks },
                    { label: 'Projects', value: stats.projects },
                    {
                        label: 'Done today',
                        value: stats.done_today,
                        tone: 'success',
                    },
                ]}
                actions={
                    <div className="flex flex-wrap gap-2">
                        {canCreateTask && projectOptions.length > 0 && (
                            <CreateTaskDialog
                                organizationId={organization.id}
                                projects={projectOptions}
                            />
                        )}
                        {canStartOnboarding && (
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
                        )}
                        <Button asChild variant="ghost" size="sm">
                            <Link
                                href={TaskController.index.url(
                                    organization.id,
                                )}
                            >
                                All tasks
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>
                }
            >
                <p className="-mt-2 text-sm text-muted-foreground">{todayLabel}</p>

                {projects.length > 0 && (
                    <section className="space-y-3">
                        <div className="flex items-center justify-between gap-2">
                            <h2 className="flex items-center gap-2 text-sm font-semibold">
                                <FolderKanban className="size-4 text-muted-foreground" />
                                Active projects
                            </h2>
                            <Button asChild variant="ghost" size="sm" className="h-8 text-xs">
                                <Link
                                    href={ProjectController.index.url(
                                        organization.id,
                                    )}
                                >
                                    View all
                                </Link>
                            </Button>
                        </div>
                        <div className="flex gap-3 overflow-x-auto pb-1">
                            {projects.map((project) => (
                                <ProjectQuickCard
                                    key={project.id}
                                    organizationId={organization.id}
                                    project={project}
                                />
                            ))}
                        </div>
                    </section>
                )}

                <div className="grid gap-6 xl:grid-cols-[minmax(0,22rem)_1fr]">
                    <div className="flex flex-col gap-6 rounded-lg border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                        <CommandCentreSection
                            title="Today's focus"
                            count={activeFocusCount}
                            description={`${activeFocusCount} of ${focusCap} slots`}
                        >
                            {focusPins.length === 0 ? (
                                <EmptyState>
                                    Pin tasks from the queue or set a due date
                                    for today.
                                </EmptyState>
                            ) : (
                                <ul className="space-y-0.5">
                                    {focusPins.map((pin, index) => (
                                        <FocusPinRow
                                            key={pin.id}
                                            organizationId={organization.id}
                                            pin={pin}
                                            index={index}
                                            canToggleDone={canToggleDone}
                                            canUnpin={canUnpinFocus}
                                        />
                                    ))}
                                </ul>
                            )}
                        </CommandCentreSection>

                        <div className="border-t border-border/60" />

                        <CommandCentreSection
                            title="Assigned to me"
                            count={assignedToMe.length}
                            description="Your open work — tap a card to open, or check to complete."
                        >
                            <AssignedToMeList
                                organizationId={organization.id}
                                tasks={assignedToMe}
                                permissions={permissions.org}
                                onViewAllInQueue={() =>
                                    applyFilters(organization.id, {
                                        assignee_member_id: currentMember.id,
                                    })
                                }
                            />
                        </CommandCentreSection>

                        {(reminders.length > 0 || notes.length > 0) && (
                            <div className="border-t border-border/60" />
                        )}

                        {reminders.length > 0 && (
                            <CommandCentreSection
                                title="Reminders"
                                count={reminders.length}
                            >
                                <ul className="space-y-2">
                                    {reminders.slice(0, 4).map((reminder) => (
                                        <li key={reminder.id}>
                                            <Link
                                                href={TaskController.show.url(
                                                    [
                                                        organization.id,
                                                        reminder.id,
                                                    ],
                                                )}
                                                className="block rounded-md px-2 py-1.5 text-sm font-medium hover:bg-muted/50 hover:text-primary"
                                            >
                                                <span className="flex items-center gap-2">
                                                    <Bell className="size-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                                                    {reminder.title}
                                                </span>
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
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </CommandCentreSection>
                        )}

                        <CommandCentreSection
                            title="Notes"
                            count={notes.length}
                            action={
                                canCreateNote ? (
                                    <CreateNoteDialog
                                        organizationId={organization.id}
                                    />
                                ) : undefined
                            }
                        >
                            {notes.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Quick notes for yourself.
                                </p>
                            ) : (
                                <ul className="space-y-2">
                                    {notes.slice(0, 4).map((note) => (
                                        <li
                                            key={note.id}
                                            className="flex gap-2 rounded-md bg-muted/30 px-2.5 py-2 text-sm leading-relaxed text-foreground/90"
                                        >
                                            <StickyNote className="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                                            <span className="line-clamp-3">
                                                {note.body}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CommandCentreSection>
                    </div>

                    <div className="flex min-w-0 flex-col gap-4 rounded-lg border border-border/70 bg-card shadow-sm">
                        <div className="border-b border-border/60 px-4 py-3 sm:px-5">
                            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <h2 className="flex items-center gap-2 text-sm font-semibold">
                                    <CheckSquare className="size-4 text-muted-foreground" />
                                    Work queue
                                    <span className="font-normal text-muted-foreground">
                                        {tasks.length}
                                    </span>
                                </h2>
                            </div>
                            <WorkspaceToolbar>
                                <div className="flex flex-wrap gap-1.5">
                                    <FilterPill
                                        label="Everyone"
                                        active={
                                            filters.assignee_member_id ===
                                            null
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
                                            variant="blue"
                                            onClick={() =>
                                                applyFilters(organization.id, {
                                                    assignee_member_id:
                                                        member.id,
                                                })
                                            }
                                        />
                                    ))}
                                </div>
                            </WorkspaceToolbar>
                        </div>

                        <div className="min-h-[12rem] px-2 pb-3 sm:px-3">
                            {tasks.length === 0 ? (
                                <EmptyState className="py-12">
                                    No tasks in this view. Adjust filters or
                                    create a task.
                                </EmptyState>
                            ) : (
                                <ul className="divide-y divide-border/40">
                                    {tasks.map((task) => (
                                        <li
                                            key={task.id}
                                            className="flex items-center gap-1"
                                        >
                                            <TaskListRow
                                                organizationId={
                                                    organization.id
                                                }
                                                task={task}
                                                permissions={permissions.org}
                                                showCompleteAction
                                                className="flex-1 border-0 px-2"
                                            />
                                            {canPinFocus && !task.is_done && (
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    className="mr-1 size-8 shrink-0 text-muted-foreground"
                                                    title="Add to today's focus"
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
                        </div>
                    </div>
                </div>
            </PageShell>
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
