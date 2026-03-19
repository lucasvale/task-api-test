<?php

namespace App\Application\Comment\DTOs;

use Spatie\LaravelData\Data;

class CreateCommentRequestDto extends Data
{
    public function __construct(
        public string $body,
    ) {
    }
}
