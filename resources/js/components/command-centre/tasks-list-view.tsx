import { EmptyState } from '@/components/command-centre/empty-state';
import {
    TaskListRow,
    type TaskListRowTask,
} from '@/components/command-centre/task-list-row';

export type TasksListItem = TaskListRowTask & {
    project_id: number;
    project_name: string | undefined;
};

type TasksListViewProps = {
    organizationId: number;
    tasks: TasksListItem[];
};

export function TasksListView({ organizationId, tasks }: TasksListViewProps) {
    if (tasks.length === 0) {
        return (
            <EmptyState>
                No tasks match your visibility scope.
            </EmptyState>
        );
    }

    return (
        <ul className="divide-y divide-border/50">
            {tasks.map((task) => (
                <li key={task.id}>
                    <TaskListRow
                        organizationId={organizationId}
                        task={task}
                    />
                </li>
            ))}
        </ul>
    );
}
