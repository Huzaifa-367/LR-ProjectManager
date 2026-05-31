import AttachmentController from '@/actions/App/Http/Controllers/CommandCentre/AttachmentController';
import TaskCommentController from '@/actions/App/Http/Controllers/CommandCentre/TaskCommentController';
import TaskController from '@/actions/App/Http/Controllers/CommandCentre/TaskController';
import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
    const [commentBody, setCommentBody] = useState('');
    const canToggle = canOrg(permissions.org, 'org.tasks.toggle-done');
    const canDelete = canOrg(permissions.org, 'org.tasks.destroy');
    const canComment = canOrg(permissions.org, 'org.task-comments.store');
    const canUpload = canOrg(permissions.org, 'org.attachments.store');

    return (
        <>
            <Head title={task.title} />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={task.title}
                        description={`${task.project_name ?? 'Project'} · ${task.kind}`}
                    />
                    <Button asChild variant="outline">
                        <Link
                            href={TaskController.index.url(organization.id)}
                        >
                            All tasks
                        </Link>
                    </Button>
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm">
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="outline">{task.status}</Badge>
                            {task.is_done && <Badge>Done</Badge>}
                        </div>
                        {task.description && <p>{task.description}</p>}
                        {task.assignees.length > 0 && (
                            <div>
                                <p className="mb-1 font-medium">Assignees</p>
                                <ul className="list-inside list-disc text-muted-foreground">
                                    {task.assignees.map((assignee) => (
                                        <li key={assignee.id}>
                                            {assignee.display_name ??
                                                'Member'}{' '}
                                            {assignee.is_primary && '(primary)'}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Comments</CardTitle>
                        <CardDescription>
                            Discussion thread for this task.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {comments.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No comments yet.
                            </p>
                        ) : (
                            comments.map((comment) => (
                                <div
                                    key={comment.id}
                                    className="rounded-md border px-3 py-2 text-sm"
                                >
                                    <div className="mb-1 flex items-center justify-between gap-2">
                                        <span className="font-medium">
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
                                    <p className="whitespace-pre-wrap">
                                        {comment.body}
                                    </p>
                                    {comment.edited_at && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Edited
                                        </p>
                                    )}
                                </div>
                            ))
                        )}

                        {canComment && (
                            <form
                                {...TaskCommentController.store.form({
                                    organization: organization.id,
                                    task: task.id,
                                })}
                                className="space-y-3 border-t pt-4"
                            >
                                <div className="space-y-2">
                                    <Label htmlFor="comment_body">
                                        Add comment
                                    </Label>
                                    <Textarea
                                        id="comment_body"
                                        name="body"
                                        value={commentBody}
                                        onChange={(event) =>
                                            setCommentBody(event.target.value)
                                        }
                                        rows={3}
                                        required
                                    />
                                </div>
                                <Button type="submit">Post comment</Button>
                            </form>
                        )}
                    </CardContent>
                </Card>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Attachments</CardTitle>
                        <CardDescription>
                            Files linked to this task (max 25 MB).
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {attachments.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No attachments yet.
                            </p>
                        ) : (
                            attachments.map((attachment) => (
                                <div
                                    key={attachment.id}
                                    className="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {attachment.original_filename}
                                        </p>
                                        <p className="text-muted-foreground">
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
                                </div>
                            ))
                        )}

                        {canUpload && (
                            <Form
                                {...AttachmentController.store.form(
                                    organization.id,
                                )}
                                className="space-y-3 border-t pt-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <input
                                            type="hidden"
                                            name="attachable_type"
                                            value="task"
                                        />
                                        <input
                                            type="hidden"
                                            name="attachable_id"
                                            value={task.id}
                                        />
                                        <div className="space-y-2">
                                            <Label htmlFor="attachment_file">
                                                Upload file
                                            </Label>
                                            <input
                                                id="attachment_file"
                                                name="file"
                                                type="file"
                                                required
                                            />
                                            <InputError message={errors.file} />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Upload
                                        </Button>
                                    </>
                                )}
                            </Form>
                        )}
                    </CardContent>
                </Card>

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
            </div>
        </>
    );
}
