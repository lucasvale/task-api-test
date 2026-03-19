<?php

namespace App\Application\Project\DTOs;

use Spatie\LaravelData\Data;

class UpdateProjectRequestDto extends Data
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {
    }
}
