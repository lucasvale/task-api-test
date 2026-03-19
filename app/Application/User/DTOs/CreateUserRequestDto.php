<?php

namespace App\Application\User\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Unique;

class CreateUserRequestDto extends Data
{
    public function __construct(
        public string $name,
        #[Email, Unique('users', 'email')]
        public string $email,
        public string $password,
    ) {
    }
}
