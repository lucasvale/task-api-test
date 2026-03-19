<?php

namespace App\Application\Task\DTOs;

use Spatie\LaravelData\Data;

class UpdateTaskRequestDto extends Data
{
    public function __construct(
        public string $title,
        public ?string $description = null,
        public string $status = 'todo',
        public ?int $assigned_to = null,
        public ?string $due_date = null,
    ) {
    }
}
