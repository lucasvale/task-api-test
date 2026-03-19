<?php

namespace App\Application\Comment\DTOs;

use Spatie\LaravelData\Data;

class UpdateCommentRequestDto extends Data
{
    public function __construct(
        public string $body,
    ) {
    }
}
