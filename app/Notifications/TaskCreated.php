<?php

namespace App\Notifications;

use App\Domain\Task\Entities\TaskEntity;
use Carbon\Carbon;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCreated extends Notification implements ShouldQueue
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
            ->subject('New Task Assigned: ' . $this->task->title)
            ->markdown('emails.task.created', [
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
            'message' => 'You have been assigned to a new task: ' . $this->task->title,
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
        return 'task-created';
    }

    public function initialDatabaseReadAtValue(): ?Carbon
    {
        return null;
    }
}
