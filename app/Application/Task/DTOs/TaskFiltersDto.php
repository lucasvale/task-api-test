<?php

namespace App\Application\Task\DTOs;

use Spatie\LaravelData\Data;

class TaskFiltersDto extends Data
{
    public function __construct(
        public ?string $status = null,
        public ?string $due_date_from = null,
        public ?string $due_date_to = null,
        public ?string $search = null,
    ) {
    }
}
