import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import ExportController from '@/actions/App/Http/Controllers/ExportController';
import { CommandCard } from '@/components/command-centre/command-card';
import { CreateTaskDialog } from '@/components/command-centre/create-task-dialog';
import { PageShell } from '@/components/command-centre/page-shell';
import { ProjectKanban } from '@/components/command-centre/project-kanban';
import type { TaskTeamMemberOption } from '@/components/command-centre/task-assignee-picker';
import { TasksCalendarView } from '@/components/command-centre/tasks-calendar-view';
import type { TasksListItem } from '@/components/command-centre/tasks-list-view';
import { TasksListView } from '@/components/command-centre/tasks-list-view';
import { TasksViewToolbar } from '@/components/command-centre/tasks-view-toolbar';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
import { useTasksViewPreference } from '@/hooks/use-tasks-view-preference';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type TasksIndexProps = {
    organization: OrganizationSummary;
    tasks: TasksListItem[];
    projects: Array<{ id: number; name: string }>;
    projectTeams: Record<number, TaskTeamMemberOption[]>;
    filters: { project_id: number | null; kind: string | null };
    permissions: CommandCentrePermissions;
};

export default function TasksIndex({
    organization,
    tasks,
    projects,
    projectTeams,
    filters,
    permissions,
}: TasksIndexProps) {
    const canCreate = canOrg(permissions.org, 'org.tasks.store');
    const canExport = canOrg(permissions.org, 'org.exports.store');
    const { view, calendarPeriod, setView, setCalendarPeriod } =
        useTasksViewPreference();

    const requestExport = (): void => {
        router.post(
            ExportController.store.url(organization.id),
            {
                export_type: 'tasks_csv',
                filters: {
                    project_id: filters.project_id,
                    kind: filters.kind,
                },
            },
            { preserveScroll: true },
        );
    };

    const viewDescription =
        view === 'kanban'
            ? 'Drag cards between columns to update status.'
            : view === 'calendar'
              ? 'Tasks are grouped by due date. Use month, week, or day to change the calendar layout.'
              : 'Use the header project selector to filter by project.';

    return (
        <>
            <Head title="Tasks" />
            <PageShell
                title="Tasks"
                subtitle={organization.name}
                stats={[{ label: 'Visible', value: tasks.length }]}
                actions={
                    <div className="flex flex-wrap gap-2">
                        {canCreate && projects.length > 0 && (
                            <CreateTaskDialog
                                organizationId={organization.id}
                                projects={projects}
                                projectTeams={projectTeams}
                            />
                        )}
                        {canExport && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={requestExport}
                            >
                                <Download className="size-4" />
                                Export CSV
                            </Button>
                        )}
                    </div>
                }
            >
                <CommandCard
                    title="All tasks"
                    description={viewDescription}
                    dot="crimson"
                >
                    <div className="mb-4 border-b border-border/50 pb-4">
                        <TasksViewToolbar
                            view={view}
                            calendarPeriod={calendarPeriod}
                            onViewChange={setView}
                            onCalendarPeriodChange={setCalendarPeriod}
                        />
                    </div>
                    {view === 'list' && (
                        <TasksListView
                            organizationId={organization.id}
                            tasks={tasks}
                        />
                    )}
                    {view === 'kanban' && (
                        <ProjectKanban
                            organizationId={organization.id}
                            tasks={tasks}
                            permissions={permissions}
                        />
                    )}
                    {view === 'calendar' && (
                        <TasksCalendarView
                            organizationId={organization.id}
                            tasks={tasks}
                            period={calendarPeriod}
                        />
                    )}
                </CommandCard>
            </PageShell>
        </>
    );
}
