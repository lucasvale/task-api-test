<?php

namespace App\Application\Notification\DTOs;

use Spatie\LaravelData\Data;

class NotificationFiltersDto extends Data
{
    public function __construct(
        public ?string $type = null,
        public ?bool $read = null,
        public ?string $from = null,
        public ?string $to = null,
    ) {
    }
}
