<?php

namespace App\Domain\Notification\Repositories;

use App\Application\Notification\DTOs\NotificationFiltersDto;
use App\Domain\Notification\Entities\NotificationEntity;

interface NotificationRepositoryInterface
{
    /**
     * @return NotificationEntity[]
     */
    public function findAllByUserId(int $userId, NotificationFiltersDto $filters): array;

    public function findByIdAndUserId(string $id, int $userId): ?NotificationEntity;

    public function markAsRead(string $id): void;

    public function markAllAsReadByUserId(int $userId): void;
}
