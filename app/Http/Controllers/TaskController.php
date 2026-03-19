<?php

namespace App\Http\Controllers;

use App\Application\Task\DTOs\CreateTaskRequestDto;
use App\Application\Task\DTOs\TaskFiltersDto;
use App\Application\Task\DTOs\UpdateTaskRequestDto;
use App\Domain\Task\Services\TaskService;
use App\Enum\HttpStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends ApiController
{
    public function __construct(
        private readonly TaskService $taskService
    ) {
    }

    public function index(Request $request, int $projectId, TaskFiltersDto $filters): JsonResponse
    {
        try {
            $tasks = $this->taskService->listTasks($projectId, $request->user()->id, $filters);

            return $this->successResponse(
                array_map(fn ($task) => $task->toArray(), $tasks),
            );
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function show(Request $request, int $projectId, int $id): JsonResponse
    {
        try {
            $task = $this->taskService->getTask($id, $request->user()->id);

            return $this->successResponse($task->toArray());
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function create(Request $request, int $projectId, CreateTaskRequestDto $dto): JsonResponse
    {
        try {
            $task = $this->taskService->createTask($dto, $projectId, $request->user()->id);

            return $this->successResponse(
                $task->toArray(),
                HttpStatus::HTTP_CREATED,
            );
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function update(Request $request, int $projectId, int $id, UpdateTaskRequestDto $dto): JsonResponse
    {
        try {
            $task = $this->taskService->updateTask($id, $dto, $request->user()->id);

            return $this->successResponse($task->toArray());
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function destroy(Request $request, int $projectId, int $id): JsonResponse
    {
        try {
            $this->taskService->deleteTask($id, $request->user()->id);

            return $this->successResponse([], HttpStatus::HTTP_NO_CONTENT);
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }
}
