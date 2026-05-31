import AttachmentController from '@/actions/App/Http/Controllers/CommandCentre/AttachmentController';
import TaskCommentController from '@/actions/App/Http/Controllers/CommandCentre/TaskCommentController';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Head, Link, router } from '@inertiajs/react';
import { AddCommentDialog } from '@/components/command-centre/add-comment-dialog';
import { CommandCard } from '@/components/command-centre/command-card';
import { EditTaskDialog } from '@/components/command-centre/edit-task-dialog';
import { EmptyState } from '@/components/command-centre/empty-state';
import { PageShell } from '@/components/command-centre/page-shell';
import { StatusPill } from '@/components/command-centre/status-pill';
import { UploadAttachmentDialog } from '@/components/command-centre/upload-attachment-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { canOrg } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';

type TaskDetail = {
    id: number;
    kind: string;
    project_id: number;
    project_name: string | undefined;
    title: string;
    description: string | null;
    status: string;
    external_link?: string | null;
    is_done: boolean;
    assignees: Array<{
        id: number;
        display_name: string | null;
        is_primary: boolean;
    }>;
};

type TaskCommentItem = {
    id: number;
    body: string;
    author: { id: number | undefined; display_name: string | null | undefined };
    edited_at: string | null;
    created_at: string | null;
    can_edit: boolean;
};

type AttachmentItem = {
    id: number;
    original_filename: string;
    mime_type: string;
    size_bytes: number;
    uploaded_by: string | null | undefined;
    created_at: string | null;
    can_delete: boolean;
};

type TaskShowProps = {
    organization: OrganizationSummary;
    task: TaskDetail;
    comments: TaskCommentItem[];
    attachments: AttachmentItem[];
    permissions: CommandCentrePermissions;
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function TaskShow({
    organization,
    task,
    comments,
    attachments,
    permissions,
}: TaskShowProps) {
    const canUpdate = canOrg(permissions.org, 'org.tasks.update');
    const canToggle = canOrg(permissions.org, 'org.tasks.toggle-done');
    const canDelete = canOrg(permissions.org, 'org.tasks.destroy');
    const canComment = canOrg(permissions.org, 'org.task-comments.store');
    const canUpload = canOrg(permissions.org, 'org.attachments.store');

    return (
        <>
            <Head title={task.title} />
            <PageShell
                title={task.title}
                subtitle={`${task.project_name ?? 'Project'} · ${task.kind}`}
                actions={
                    <div className="flex flex-wrap gap-2">
                        {canUpdate && (
                            <EditTaskDialog
                                organizationId={organization.id}
                                task={task}
                            />
                        )}
                        <Button asChild variant="outline" size="sm">
                            <Link
                                href={TaskController.index.url(organization.id)}
                            >
                                All tasks
                            </Link>
                        </Button>
                    </div>
                }
            >
                <CommandCard title="Details" dot="crimson">
                    <div className="space-y-4 text-sm">
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusPill status={task.status} />
                            {task.is_done && <Badge>Done</Badge>}
                        </div>
                        {task.description ? (
                            <p className="whitespace-pre-wrap text-muted-foreground">
                                {task.description}
                            </p>
                        ) : (
                            <EmptyState>No description yet.</EmptyState>
                        )}
                        {task.external_link && (
                            <a
                                href={task.external_link}
                                target="_blank"
                                rel="noreferrer"
                                className="text-sm text-primary hover:underline"
                            >
                                Open external link
                            </a>
                        )}
                        {task.assignees.length > 0 && (
                            <div>
                                <p className="mb-2 text-[10px] font-bold tracking-[0.18em] text-muted-foreground uppercase">
                                    Assignees
                                </p>
                                <ul className="divide-y divide-border/50 rounded-lg border border-border/60">
                                    {task.assignees.map((assignee) => (
                                        <li
                                            key={assignee.id}
                                            className="tcm-list-row"
                                        >
                                            <span>
                                                {assignee.display_name ??
                                                    'Member'}
                                            </span>
                                            {assignee.is_primary && (
                                                <Badge variant="secondary">
                                                    Primary
                                                </Badge>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                </CommandCard>

                <CommandCard
                    title="Comments"
                    description="Discussion thread for this task."
                    dot="blue"
                    action={
                        canComment ? (
                            <AddCommentDialog
                                organizationId={organization.id}
                                taskId={task.id}
                            />
                        ) : undefined
                    }
                >
                    {comments.length === 0 ? (
                        <EmptyState>No comments yet.</EmptyState>
                    ) : (
                        <ul className="divide-y divide-border/50">
                            {comments.map((comment) => (
                                <li key={comment.id} className="py-3">
                                    <div className="mb-1 flex items-center justify-between gap-2">
                                        <span className="text-sm font-medium">
                                            {comment.author.display_name ??
                                                'Member'}
                                        </span>
                                        {comment.can_edit && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => {
                                                    router.delete(
                                                        TaskCommentController.destroy.url(
                                                            [
                                                                organization.id,
                                                                task.id,
                                                                comment.id,
                                                            ],
                                                        ),
                                                    );
                                                }}
                                            >
                                                Delete
                                            </Button>
                                        )}
                                    </div>
                                    <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                                        {comment.body}
                                    </p>
                                    {comment.edited_at && (
                                        <p className="mt-1 text-xs text-muted-foreground/80">
                                            Edited
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </CommandCard>

                <CommandCard
                    title="Attachments"
                    description="Files linked to this task."
                    dot="gold"
                    action={
                        canUpload ? (
                            <UploadAttachmentDialog
                                organizationId={organization.id}
                                taskId={task.id}
                            />
                        ) : undefined
                    }
                >
                    {attachments.length === 0 ? (
                        <EmptyState>No attachments yet.</EmptyState>
                    ) : (
                        <ul className="divide-y divide-border/50">
                            {attachments.map((attachment) => (
                                <li
                                    key={attachment.id}
                                    className="tcm-list-row items-center justify-between"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {attachment.original_filename}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatBytes(attachment.size_bytes)}
                                            {attachment.uploaded_by
                                                ? ` · ${attachment.uploaded_by}`
                                                : ''}
                                        </p>
                                    </div>
                                    {attachment.can_delete && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                router.delete(
                                                    AttachmentController.destroy.url(
                                                        [
                                                            organization.id,
                                                            attachment.id,
                                                        ],
                                                    ),
                                                );
                                            }}
                                        >
                                            Remove
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </CommandCard>

                <div className="flex flex-wrap gap-3">
                    {canToggle && (
                        <Button
                            onClick={() => {
                                router.patch(
                                    TaskController.toggleDone.url([
                                        organization.id,
                                        task.id,
                                    ]),
                                );
                            }}
                        >
                            {task.is_done ? 'Mark incomplete' : 'Mark done'}
                        </Button>
                    )}
                    {canDelete && (
                        <Button
                            variant="destructive"
                            onClick={() => {
                                router.delete(
                                    TaskController.destroy.url([
                                        organization.id,
                                        task.id,
                                    ]),
                                );
                            }}
                        >
                            Delete task
                        </Button>
                    )}
                </div>
            </PageShell>
        </>
    );
}
