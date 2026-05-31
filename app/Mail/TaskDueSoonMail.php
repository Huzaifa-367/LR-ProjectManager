<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskDueSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
    ) {
        $this->task->loadMissing(['organization', 'project']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Task due soon: :task', ['task' => $this->task->title]),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.task-due-soon',
            with: [
                'taskTitle' => $this->task->title,
                'projectName' => $this->task->project?->name,
                'deadlineDate' => $this->task->deadline_date?->format('M j, Y g:i A'),
                'actionUrl' => route('organizations.tasks.show', [$this->task->organization, $this->task]),
            ],
        );
    }
}
