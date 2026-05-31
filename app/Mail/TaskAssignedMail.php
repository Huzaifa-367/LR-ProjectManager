<?php

namespace App\Mail;

use App\Models\OrganizationMember;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public ?OrganizationMember $assignedBy = null,
    ) {
        $this->task->loadMissing(['organization', 'project']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Task assigned: :task', ['task' => $this->task->title]),
        );
    }

    public function content(): Content
    {
        $assignedByName = $this->assignedBy?->display_name;

        return new Content(
            text: 'mail.task-assigned',
            with: [
                'taskTitle' => $this->task->title,
                'projectName' => $this->task->project?->name,
                'assignedByName' => $assignedByName,
                'actionUrl' => route('organizations.tasks.show', [$this->task->organization, $this->task]),
            ],
        );
    }
}
