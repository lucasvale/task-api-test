<?php

namespace App\Domain\Project\Entities;

use App\Application\Project\DTOs\CreateProjectRequestDto;
use App\Application\Project\DTOs\UpdateProjectRequestDto;
use App\Models\Project;

class ProjectEntity
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public string $name,
        public ?string $description,
    ) {
    }

    public static function fromCreateDto(CreateProjectRequestDto $dto, int $userId): self
    {
        return new self(
            null,
            $userId,
            $dto->name,
            $dto->description,
        );
    }

    public static function fromModel(Project $project): self
    {
        return new self(
            $project->id,
            $project->user_id,
            $project->name,
            $project->description,
        );
    }

    public function applyUpdate(UpdateProjectRequestDto $dto): void
    {
        $this->name = $dto->name;
        $this->description = $dto->description;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
