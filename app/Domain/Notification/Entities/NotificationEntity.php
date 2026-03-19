<?php

namespace App\Domain\Notification\Entities;

use Illuminate\Notifications\DatabaseNotification;

class NotificationEntity
{
    public function __construct(
        public string $id,
        public int $userId,
        public string $type,
        public array $data,
        public ?string $readAt,
        public string $createdAt,
    ) {
    }

    public static function fromModel(DatabaseNotification $notification): self
    {
        return new self(
            $notification->id,
            $notification->notifiable_id,
            $notification->type,
            $notification->data,
            $notification->read_at?->toIso8601String(),
            $notification->created_at->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'data' => $this->data,
            'read_at' => $this->readAt,
            'created_at' => $this->createdAt,
        ];
    }
}
