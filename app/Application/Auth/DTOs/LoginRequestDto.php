<?php

namespace App\Application\Auth\DTOs;

use Spatie\LaravelData\Data;

class LoginRequestDto extends Data
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
