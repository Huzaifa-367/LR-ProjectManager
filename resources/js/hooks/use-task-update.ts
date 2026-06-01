import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { router } from '@inertiajs/react';
import { useCallback } from 'react';

export function useTaskUpdate(organizationId: number, taskId: number) {
    const updateUrl = TaskController.update.url([organizationId, taskId]);
    const assigneesUrl = TaskController.syncAssignees.url([
        organizationId,
        taskId,
    ]);

    const patchTask = useCallback(
        (data: Record<string, unknown>) => {
            router.patch(updateUrl, data, { preserveScroll: true });
        },
        [updateUrl],
    );

    const syncAssignees = useCallback(
        (assigneeMemberIds: number[]) => {
            router.put(
                assigneesUrl,
                { assignee_member_ids: assigneeMemberIds },
                { preserveScroll: true },
            );
        },
        [assigneesUrl],
    );

    return { patchTask, syncAssignees };
}
