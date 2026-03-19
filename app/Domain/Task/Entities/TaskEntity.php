<?php

namespace App\Domain\Task\Entities;

use App\Application\Task\DTOs\CreateTaskRequestDto;
use App\Application\Task\DTOs\UpdateTaskRequestDto;
use App\Models\Task;

class TaskEntity
{
    public function __construct(
        public ?int $id,
        public int $projectId,
        public ?int $assignedTo,
        public string $title,
        public ?string $description,
        public string $status,
        public ?string $dueDate,
    ) {
    }

    public static function fromCreateDto(CreateTaskRequestDto $dto, int $projectId): self
    {
        return new self(
            null,
            $projectId,
            $dto->assigned_to,
            $dto->title,
            $dto->description,
            $dto->status,
            $dto->due_date,
        );
    }

    public static function fromModel(Task $task): self
    {
        return new self(
            $task->id,
            $task->project_id,
            $task->assigned_to,
            $task->title,
            $task->description,
            $task->status,
            $task->due_date?->toDateString(),
        );
    }

    public function applyUpdate(UpdateTaskRequestDto $dto): void
    {
        $this->title = $dto->title;
        $this->description = $dto->description;
        $this->status = $dto->status;
        $this->assignedTo = $dto->assigned_to;
        $this->dueDate = $dto->due_date;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->projectId,
            'assigned_to' => $this->assignedTo,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'due_date' => $this->dueDate,
        ];
    }
}
