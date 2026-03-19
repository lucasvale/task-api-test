<?php

namespace App\Infrastructure\Database\Repositories;

use App\Application\Notification\DTOs\NotificationFiltersDto;
use App\Domain\Notification\Entities\NotificationEntity;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\User;

class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * @return NotificationEntity[]
     */
    public function findAllByUserId(int $userId, NotificationFiltersDto $filters): array
    {
        $query = DatabaseNotification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->orderBy('created_at', 'desc');

        if ($filters->type !== null) {
            $query->where('type', $filters->type);
        }

        if ($filters->read !== null) {
            if ($filters->read) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        if ($filters->from !== null) {
            $query->where('created_at', '>=', $filters->from);
        }

        if ($filters->to !== null) {
            $query->where('created_at', '<=', $filters->to);
        }

        return $query->get()
            ->map(fn (DatabaseNotification $n) => NotificationEntity::fromModel($n))
            ->toArray();
    }

    public function findByIdAndUserId(string $id, int $userId): ?NotificationEntity
    {
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->first();

        return $notification ? NotificationEntity::fromModel($notification) : null;
    }

    public function markAsRead(string $id): void
    {
        DatabaseNotification::where('id', $id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markAllAsReadByUserId(int $userId): void
    {
        DatabaseNotification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
