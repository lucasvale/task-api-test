<?php

namespace App\Notifications;

use App\Domain\Task\Entities\TaskEntity;
use Carbon\Carbon;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TaskEntity $task,
        private readonly string $projectName,
    ) {
        $this->onConnection('redis');
        $this->onQueue('task_management_queue');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/api/projects/' . $this->task->projectId . '/tasks/' . $this->task->id);

        return (new MailMessage())
            ->subject('Task Updated: ' . $this->task->title)
            ->markdown('emails.task.updated', [
                'assigneeName' => $notifiable->name,
                'projectName' => $this->projectName,
                'taskTitle' => $this->task->title,
                'taskDescription' => $this->task->description,
                'taskStatus' => $this->task->status,
                'taskDueDate' => $this->task->dueDate,
                'url' => $url,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'project_id' => $this->task->projectId,
            'title' => $this->task->title,
            'description' => $this->task->description,
            'assigned_to' => $this->task->assignedTo,
            'message' => 'A task assigned to you has been updated: ' . $this->task->title,
        ];
    }

    public function backoff(): int
    {
        return 3;
    }

    public function retryUntil(): DateTime
    {
        return now()->plus(minutes: 5);
    }

    public function databaseType(object $notifiable): string
    {
        return 'task-updated';
    }

    public function initialDatabaseReadAtValue(): ?Carbon
    {
        return null;
    }
}
