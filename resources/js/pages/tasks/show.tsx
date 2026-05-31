import AttachmentController from '@/actions/App/Http/Controllers/CommandCentre/AttachmentController';
import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import TaskCommentController from '@/actions/App/Http/Controllers/CommandCentre/TaskCommentController';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Form, Head, Link, router } from '@inertiajs/react';
import { AddCommentDialog } from '@/components/command-centre/add-comment-dialog';
import { CommandCard } from '@/components/command-centre/command-card';
import { EditTaskDialog } from '@/components/command-centre/edit-task-dialog';
import { EmptyState } from '@/components/command-centre/empty-state';
import { ExpandableText } from '@/components/command-centre/expandable-text';
import { PageShell } from '@/components/command-centre/page-shell';
import { StatusPill } from '@/components/command-centre/status-pill';
import { TaskKindBadge } from '@/components/command-centre/task-kind-badge';
import { UploadAttachmentDialog } from '@/components/command-centre/upload-attachment-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { canOrg } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';
import { cn } from '@/lib/utils';
import { formatTaskKind } from '@/lib/task-options';

type TaskDetail = {
    id: number;
    kind: string;
    project_id: number;
    project_name: string | undefined;
    title: string;
    description: string | null;
    status: string;
    priority: string | null;
    external_link?: string | null;
    is_done: boolean;
    assignees: Array<{
        id: number;
        display_name: string | null;
        is_primary: boolean;
    }>;
};

type ProjectTeamMember = {
    organization_member_id: number;
    display_name: string | null;
    role_name: string | null | undefined;
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
    projectTeam: ProjectTeamMember[];
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
    projectTeam,
    comments,
    attachments,
    permissions,
}: TaskShowProps) {
    const canUpdate = canOrg(permissions.org, 'org.tasks.update');
    const canSyncAssignees = canOrg(permissions.org, 'org.tasks.assignees.sync');
    const canToggle = canOrg(permissions.org, 'org.tasks.toggle-done');
    const canDelete = canOrg(permissions.org, 'org.tasks.destroy');
    const canComment = canOrg(permissions.org, 'org.task-comments.store');
    const canUpload = canOrg(permissions.org, 'org.attachments.store');

    const teamOptions = projectTeam.map((member) => ({
        id: member.organization_member_id,
        display_name: member.display_name,
        role_name: member.role_name,
    }));

    const selectedAssigneeIds = task.assignees.map((assignee) => assignee.id);

    return (
        <>
            <Head title={task.title} />
            <PageShell
                title={task.title}
                subtitle={task.project_name ?? 'Project'}
                stats={[
                    { label: 'Type', value: formatTaskKind(task.kind), tone: 'accent' },
                    { label: 'Status', value: task.status.replace(/_/g, ' ') },
                    {
                        label: 'Assignees',
                        value: task.assignees.length,
                    },
                ]}
                actions={
                    <div className="flex flex-wrap gap-2">
                        {canUpdate && (
                            <EditTaskDialog
                                organizationId={organization.id}
                                task={task}
                            />
                        )}
                        {task.project_id > 0 && (
                            <Button asChild variant="outline" size="sm">
                                <Link
                                    href={ProjectController.show.url([
                                        organization.id,
                                        task.project_id,
                                    ])}
                                >
                                    Project board
                                </Link>
                            </Button>
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
                <div className="grid gap-[18px] xl:grid-cols-[minmax(0,20rem)_1fr]">
                    <aside className="flex flex-col gap-[18px]">
                        <CommandCard title="Summary" dot="crimson">
                            <div className="space-y-3 text-sm">
                                <div className="flex flex-wrap items-center gap-2">
                                    <TaskKindBadge kind={task.kind} />
                                    <StatusPill status={task.status} />
                                    {task.is_done && (
                                        <Badge variant="secondary">Done</Badge>
                                    )}
                                </div>
                                {task.priority && (
                                    <p className="text-xs text-muted-foreground">
                                        Priority:{' '}
                                        <span className="font-medium capitalize text-foreground">
                                            {task.priority}
                                        </span>
                                    </p>
                                )}
                                {task.external_link && (
                                    <a
                                        href={task.external_link}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-block text-sm text-primary hover:underline"
                                    >
                                        Open external link
                                    </a>
                                )}
                            </div>
                        </CommandCard>

                        <CommandCard
                            title="Project team"
                            description="Assign members already on this project."
                            dot="blue"
                            badge={
                                projectTeam.length > 0 ? (
                                    <Badge variant="secondary">
                                        {projectTeam.length}
                                    </Badge>
                                ) : undefined
                            }
                        >
                            {canSyncAssignees ? (
                                <Form
                                    {...TaskController.syncAssignees.form([
                                        organization.id,
                                        task.id,
                                    ])}
                                    className="space-y-3"
                                >
                                    {({ processing }) => (
                                        <>
                                            {teamOptions.length === 0 ? (
                                                <EmptyState>
                                                    Add members to the project
                                                    team first.
                                                </EmptyState>
                                            ) : (
                                                <ul className="space-y-2">
                                                    {teamOptions.map((member) => {
                                                        const isSelected =
                                                            selectedAssigneeIds.includes(
                                                                member.id,
                                                            );

                                                        return (
                                                            <li key={member.id}>
                                                                <label
                                                                    className={cn(
                                                                        'flex cursor-pointer items-center gap-3 rounded-lg border border-border/60 px-3 py-2',
                                                                        isSelected &&
                                                                            'border-primary/30 bg-primary/5',
                                                                        processing &&
                                                                            'opacity-60',
                                                                    )}
                                                                >
                                                                    <input
                                                                        type="checkbox"
                                                                        name="assignee_member_ids[]"
                                                                        value={
                                                                            member.id
                                                                        }
                                                                        defaultChecked={
                                                                            isSelected
                                                                        }
                                                                        disabled={
                                                                            processing
                                                                        }
                                                                        className="size-4 rounded border-input"
                                                                    />
                                                                    <span className="min-w-0 flex-1">
                                                                        <span className="block text-sm font-medium">
                                                                            {member.display_name ??
                                                                                'Member'}
                                                                        </span>
                                                                        {member.role_name && (
                                                                            <span className="text-xs text-muted-foreground">
                                                                                {
                                                                                    member.role_name
                                                                                }
                                                                            </span>
                                                                        )}
                                                                    </span>
                                                                </label>
                                                            </li>
                                                        );
                                                    })}
                                                </ul>
                                            )}
                                            <Button
                                                type="submit"
                                                size="sm"
                                                variant="secondary"
                                                disabled={
                                                    processing ||
                                                    teamOptions.length === 0
                                                }
                                            >
                                                {processing && <Spinner />}
                                                Save assignees
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            ) : projectTeam.length === 0 ? (
                                <EmptyState>
                                    No project team members yet.
                                </EmptyState>
                            ) : (
                                <ul className="divide-y divide-border/50 rounded-lg border border-border/60">
                                    {task.assignees.length === 0 ? (
                                        <li className="px-3 py-2 text-sm text-muted-foreground">
                                            Unassigned
                                        </li>
                                    ) : (
                                        task.assignees.map((assignee) => (
                                            <li
                                                key={assignee.id}
                                                className="flex items-center justify-between gap-2 px-3 py-2"
                                            >
                                                <span className="text-sm">
                                                    {assignee.display_name ??
                                                        'Member'}
                                                </span>
                                                {assignee.is_primary && (
                                                    <Badge
                                                        variant="outline"
                                                        className="text-[10px]"
                                                    >
                                                        Primary
                                                    </Badge>
                                                )}
                                            </li>
                                        ))
                                    )}
                                </ul>
                            )}
                        </CommandCard>

                        <CommandCard title="Actions" dot="gold">
                            <div className="flex flex-col gap-2">
                                {canToggle && (
                                    <Button
                                        size="sm"
                                        onClick={() => {
                                            router.patch(
                                                TaskController.toggleDone.url([
                                                    organization.id,
                                                    task.id,
                                                ]),
                                            );
                                        }}
                                    >
                                        {task.is_done
                                            ? 'Mark incomplete'
                                            : 'Mark done'}
                                    </Button>
                                )}
                                {canDelete && (
                                    <Button
                                        size="sm"
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
                                        Delete
                                    </Button>
                                )}
                            </div>
                        </CommandCard>
                    </aside>

                    <div className="flex min-w-0 flex-col gap-[18px]">
                        <CommandCard title="Description" dot="green">
                            {task.description ? (
                                <ExpandableText
                                    text={task.description}
                                    maxLength={480}
                                    textClassName="text-sm leading-relaxed text-muted-foreground whitespace-pre-wrap"
                                />
                            ) : (
                                <EmptyState>No description yet.</EmptyState>
                            )}
                        </CommandCard>

                        <CommandCard
                            title="Comments"
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
                                                    {comment.author
                                                        .display_name ??
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
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CommandCard>

                        <CommandCard
                            title="Attachments"
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
                                                    {
                                                        attachment.original_filename
                                                    }
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {formatBytes(
                                                        attachment.size_bytes,
                                                    )}
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
                    </div>
                </div>
            </PageShell>
        </>
    );
}
