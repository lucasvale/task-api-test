<?php

namespace App\Domain\Notification\Services;

use App\Application\Notification\DTOs\NotificationFiltersDto;
use App\Domain\Notification\Entities\NotificationEntity;
use App\Infrastructure\Database\Repositories\NotificationRepository;

readonly class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {
    }

    /**
     * @return NotificationEntity[]
     */
    public function listNotifications(int $userId, NotificationFiltersDto $filters): array
    {
        return $this->notificationRepository->findAllByUserId($userId, $filters);
    }

    public function markAsRead(string $id, int $userId): NotificationEntity
    {
        $notification = $this->notificationRepository->findByIdAndUserId($id, $userId);

        if (!$notification) {
            throw new \RuntimeException('Notification not found.');
        }

        $this->notificationRepository->markAsRead($id);

        $notification->readAt = now()->toIso8601String();

        return $notification;
    }

    public function markAllAsRead(int $userId): void
    {
        $this->notificationRepository->markAllAsReadByUserId($userId);
    }
}
