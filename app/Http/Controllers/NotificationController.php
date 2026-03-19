<?php

namespace App\Http\Controllers;

use App\Application\Notification\DTOs\NotificationFiltersDto;
use App\Domain\Notification\Services\NotificationService;
use App\Enum\HttpStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function index(Request $request, NotificationFiltersDto $filters): JsonResponse
    {
        try {
            $notifications = $this->notificationService->listNotifications(
                $request->user()->id,
                $filters,
            );

            return $this->successResponse(
                array_map(fn ($n) => $n->toArray(), $notifications),
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        try {
            $notification = $this->notificationService->markAsRead($id, $request->user()->id);

            return $this->successResponse($notification->toArray());
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $this->notificationService->markAllAsRead($request->user()->id);

            return $this->successResponse([], message: 'All notifications marked as read.');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }
}
