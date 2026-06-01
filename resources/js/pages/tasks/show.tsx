import AttachmentController from '@/actions/App/Http/Controllers/CommandCentre/AttachmentController';
import ProjectController from '@/actions/App/Http/Controllers/CommandCentre/ProjectController';
import TaskCommentController from '@/actions/App/Http/Controllers/CommandCentre/TaskCommentController';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Head, Link, router } from '@inertiajs/react';
import {
    Calendar,
    CheckCircle2,
    ExternalLink,
    FileText,
    FolderKanban,
    MessageSquare,
    Paperclip,
    Trash2,
    User,
} from 'lucide-react';
import { AddCommentDialog } from '@/components/command-centre/add-comment-dialog';
import { AssigneeAvatars } from '@/components/command-centre/assignee-avatars';
import { CommandCard } from '@/components/command-centre/command-card';
import { InlineEditableDueDate } from '@/components/command-centre/inline-editable-due-date';
import { InlineEditableSelect } from '@/components/command-centre/inline-editable-select';
import { InlineEditableText } from '@/components/command-centre/inline-editable-text';
import { EmptyState } from '@/components/command-centre/empty-state';
import { PageShell } from '@/components/command-centre/page-shell';
import { StatusPill } from '@/components/command-centre/status-pill';
import { TaskDetailField } from '@/components/command-centre/task-detail-field';
import { TaskEditableAssignees } from '@/components/command-centre/task-editable-assignees';
import { TaskKey } from '@/components/command-centre/task-key';
import { TaskKindBadge } from '@/components/command-centre/task-kind-badge';
import { UploadAttachmentDialog } from '@/components/command-centre/upload-attachment-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTaskUpdate } from '@/hooks/use-task-update';
import { canOrg } from '@/hooks/use-org-permissions';
import type {
    CommandCentrePermissions,
    OrganizationSummary,
} from '@/types/organization';
import {
    TASK_PRIORITIES,
    TASK_STATUSES,
    formatTaskKind,
} from '@/lib/task-options';

type TaskDetail = {
    id: number;
    kind: string;
    project_id: number;
    project_name: string | undefined;
    title: string;
    description: string | null;
    status: string;
    priority: string | null;
    deadline_date: string | null;
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

function commentInitials(name: string | null | undefined): string {
    if (name === null || name === undefined || name.trim() === '') {
        return '?';
    }

    const parts = name.trim().split(/\s+/);

    if (parts.length >= 2) {
        return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
    }

    return name.slice(0, 2).toUpperCase();
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

    const { patchTask, syncAssignees } = useTaskUpdate(
        organization.id,
        task.id,
    );

    const teamOptions = projectTeam.map((member) => ({
        id: member.organization_member_id,
        display_name: member.display_name,
        role_name: member.role_name,
    }));

    const assigneeIds = task.assignees.map((assignee) => assignee.id);

    return (
        <>
            <Head title={task.title} />
            <PageShell
                title={
                    <InlineEditableText
                        value={task.title}
                        canEdit={canUpdate}
                        onSave={(title) => patchTask({ title })}
                        placeholder="Task title"
                        displayClassName="tcm-greeting block"
                        inputClassName="text-lg font-semibold"
                    />
                }
                breadcrumbs={[
                    {
                        title: organization.name,
                        href: `/organizations/${organization.id}/command-centre`,
                    },
                    {
                        title: 'Tasks',
                        href: TaskController.index.url(organization.id),
                    },
                    {
                        title: task.title,
                        href: TaskController.show.url([
                            organization.id,
                            task.id,
                        ]),
                    },
                ]}
                actions={
                    canToggle ? (
                        <Button
                            size="sm"
                            variant={task.is_done ? 'outline' : 'default'}
                            onClick={() => {
                                router.patch(
                                    TaskController.toggleDone.url([
                                        organization.id,
                                        task.id,
                                    ]),
                                );
                            }}
                        >
                            <CheckCircle2 className="size-4" />
                            {task.is_done ? 'Reopen' : 'Mark done'}
                        </Button>
                    ) : undefined
                }
            >
                <div className="mb-2 flex flex-wrap items-center gap-2">
                    <TaskKey taskId={task.id} />
                    <TaskKindBadge kind={task.kind} />
                    <StatusPill status={task.status} />
                    {task.is_done && (
                        <Badge variant="secondary">Completed</Badge>
                    )}
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_17.5rem]">
                    <div className="flex min-w-0 flex-col gap-6">
                        <CommandCard title="Description" dot="green">
                            <InlineEditableText
                                value={task.description ?? ''}
                                canEdit={canUpdate}
                                onSave={(description) =>
                                    patchTask({ description })
                                }
                                multiline
                                placeholder="Add a description — objectives, steps, acceptance criteria…"
                                emptyLabel="No description yet"
                                displayClassName="text-sm leading-relaxed text-foreground/90"
                            />
                        </CommandCard>

                        <CommandCard
                            title="Comments"
                            dot="blue"
                            badge={
                                comments.length > 0 ? (
                                    <Badge variant="secondary">
                                        {comments.length}
                                    </Badge>
                                ) : undefined
                            }
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
                                <EmptyState>
                                    <MessageSquare className="mx-auto mb-2 size-8 opacity-40" />
                                    No comments yet.
                                </EmptyState>
                            ) : (
                                <ul className="space-y-3">
                                    {comments.map((comment) => (
                                        <li
                                            key={comment.id}
                                            className="flex gap-3 rounded-lg border border-border/60 bg-muted/25 p-3"
                                        >
                                            <span
                                                className="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold text-muted-foreground"
                                                aria-hidden
                                            >
                                                {commentInitials(
                                                    comment.author
                                                        .display_name,
                                                )}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <div className="mb-1 flex flex-wrap items-center justify-between gap-2">
                                                    <span className="text-sm font-medium">
                                                        {comment.author
                                                            .display_name ??
                                                            'Member'}
                                                    </span>
                                                    {comment.created_at && (
                                                        <time
                                                            className="text-xs text-muted-foreground"
                                                            dateTime={
                                                                comment.created_at
                                                            }
                                                        >
                                                            {comment.created_at}
                                                        </time>
                                                    )}
                                                </div>
                                                <p className="whitespace-pre-wrap text-sm leading-relaxed text-muted-foreground">
                                                    {comment.body}
                                                </p>
                                                {comment.can_edit && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="mt-2 h-7 px-2 text-xs text-muted-foreground"
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
                                                        <Trash2 className="size-3.5" />
                                                        Delete
                                                    </Button>
                                                )}
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CommandCard>

                        <CommandCard
                            title="Attachments"
                            dot="gold"
                            badge={
                                attachments.length > 0 ? (
                                    <Badge variant="secondary">
                                        {attachments.length}
                                    </Badge>
                                ) : undefined
                            }
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
                                <EmptyState>
                                    <Paperclip className="mx-auto mb-2 size-8 opacity-40" />
                                    No files attached.
                                </EmptyState>
                            ) : (
                                <ul className="space-y-2">
                                    {attachments.map((attachment) => (
                                        <li
                                            key={attachment.id}
                                            className="flex items-center justify-between gap-3 rounded-lg border border-border/60 px-3 py-2.5"
                                        >
                                            <div className="flex min-w-0 items-center gap-3">
                                                <span className="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted">
                                                    <FileText className="size-4 text-muted-foreground" />
                                                </span>
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium">
                                                        {
                                                            attachment.original_filename
                                                        }
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatBytes(
                                                            attachment.size_bytes,
                                                        )}
                                                        {attachment.uploaded_by
                                                            ? ` · ${attachment.uploaded_by}`
                                                            : ''}
                                                    </p>
                                                </div>
                                            </div>
                                            {attachment.can_delete && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="shrink-0"
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

                    <aside className="flex flex-col gap-4">
                        <section className="rounded-lg border border-border/70 bg-card p-4 shadow-sm">
                            <h2 className="mb-3 text-sm font-semibold">
                                Details
                            </h2>
                            <dl className="space-y-4">
                                <TaskDetailField label="Type">
                                    {formatTaskKind(task.kind)}
                                </TaskDetailField>
                                <TaskDetailField label="Status">
                                    <InlineEditableSelect
                                        value={task.status}
                                        options={[...TASK_STATUSES]}
                                        canEdit={canUpdate}
                                        onSave={(status) =>
                                            patchTask({ status })
                                        }
                                        renderValue={(status) => (
                                            <StatusPill status={status} />
                                        )}
                                    />
                                </TaskDetailField>
                                <TaskDetailField label="Priority">
                                    <InlineEditableSelect
                                        value={task.priority ?? ''}
                                        options={TASK_PRIORITIES.map(
                                            (option) => ({
                                                value: option.value,
                                                label: option.label,
                                            }),
                                        )}
                                        canEdit={canUpdate}
                                        allowEmpty
                                        emptyValue=""
                                        placeholder="None"
                                        renderValue={(priority) =>
                                            priority ? (
                                                <span className="capitalize">
                                                    {TASK_PRIORITIES.find(
                                                        (entry) =>
                                                            entry.value ===
                                                            priority,
                                                    )?.label ?? priority}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    None
                                                </span>
                                            )
                                        }
                                        onSave={(priority) =>
                                            patchTask({
                                                priority:
                                                    priority === ''
                                                        ? null
                                                        : priority,
                                            })
                                        }
                                    />
                                </TaskDetailField>
                                <TaskDetailField label="Due">
                                    <div className="flex items-center gap-1.5">
                                        <Calendar className="size-3.5 shrink-0 text-muted-foreground" />
                                        <InlineEditableDueDate
                                            value={task.deadline_date}
                                            canEdit={canUpdate}
                                            onSave={(deadline_date) =>
                                                patchTask({ deadline_date })
                                            }
                                        />
                                    </div>
                                </TaskDetailField>
                                {task.project_id > 0 && (
                                    <TaskDetailField label="Project">
                                        <Link
                                            href={ProjectController.show.url([
                                                organization.id,
                                                task.project_id,
                                            ])}
                                            className="inline-flex items-center gap-1.5 font-medium text-primary hover:underline"
                                        >
                                            <FolderKanban className="size-3.5 shrink-0" />
                                            {task.project_name ?? 'Project'}
                                        </Link>
                                    </TaskDetailField>
                                )}
                                <TaskDetailField label="Link">
                                    {task.external_link || canUpdate ? (
                                        <div className="flex items-start gap-1.5">
                                            <ExternalLink className="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                                            <InlineEditableText
                                                value={
                                                    task.external_link ?? ''
                                                }
                                                canEdit={canUpdate}
                                                inputType="url"
                                                placeholder="https://…"
                                                emptyLabel="Add link"
                                                onSave={(external_link) =>
                                                    patchTask({
                                                        external_link:
                                                            external_link ===
                                                            ''
                                                                ? null
                                                                : external_link,
                                                    })
                                                }
                                                displayClassName="text-sm text-primary"
                                            />
                                        </div>
                                    ) : (
                                        <span className="text-sm text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                    {task.external_link && (
                                        <a
                                            href={task.external_link}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="mt-1 inline-block text-xs text-primary hover:underline"
                                        >
                                            Open in new tab
                                        </a>
                                    )}
                                </TaskDetailField>
                            </dl>
                        </section>

                        <section className="rounded-lg border border-border/70 bg-card p-4 shadow-sm">
                            <div className="mb-3 flex items-center justify-between gap-2">
                                <h2 className="text-sm font-semibold">
                                    People
                                </h2>
                                {task.assignees.length > 0 && (
                                    <AssigneeAvatars
                                        assignees={task.assignees}
                                    />
                                )}
                            </div>

                            {canSyncAssignees && teamOptions.length > 0 ? (
                                <TaskEditableAssignees
                                    selectedIds={assigneeIds}
                                    members={teamOptions}
                                    canEdit
                                    onSave={syncAssignees}
                                />
                            ) : task.assignees.length === 0 ? (
                                <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <User className="size-4 shrink-0" />
                                    Unassigned
                                </p>
                            ) : (
                                <ul className="space-y-2">
                                    {task.assignees.map((assignee) => (
                                        <li
                                            key={assignee.id}
                                            className="flex items-center justify-between gap-2 text-sm"
                                        >
                                            <span>
                                                {assignee.display_name ??
                                                    'Member'}
                                            </span>
                                            {assignee.is_primary && (
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs"
                                                >
                                                    Lead
                                                </Badge>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <div className="flex flex-col gap-2">
                            {task.project_id > 0 && (
                                <Button
                                    asChild
                                    variant="outline"
                                    size="sm"
                                    className="w-full justify-start"
                                >
                                    <Link
                                        href={ProjectController.show.url([
                                            organization.id,
                                            task.project_id,
                                        ])}
                                    >
                                        <FolderKanban className="size-4" />
                                        View on board
                                    </Link>
                                </Button>
                            )}
                            <Button
                                asChild
                                variant="ghost"
                                size="sm"
                                className="w-full justify-start"
                            >
                                <Link
                                    href={TaskController.index.url(
                                        organization.id,
                                    )}
                                >
                                    Back to all tasks
                                </Link>
                            </Button>
                            {canDelete && (
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    className="w-full"
                                    onClick={() => {
                                        router.delete(
                                            TaskController.destroy.url([
                                                organization.id,
                                                task.id,
                                            ]),
                                        );
                                    }}
                                >
                                    <Trash2 className="size-4" />
                                    Delete task
                                </Button>
                            )}
                        </div>
                    </aside>
                </div>
            </PageShell>
        </>
    );
}
