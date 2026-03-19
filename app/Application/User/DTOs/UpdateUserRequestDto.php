<?php

namespace App\Application\User\DTOs;

use Spatie\LaravelData\Data;

class UpdateUserRequestDto extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
    ) {
    }
}
