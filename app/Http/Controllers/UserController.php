<?php

namespace App\Http\Controllers;

use App\Application\User\DTOs\CreateUserRequestDto;
use App\Application\User\DTOs\UpdateUserRequestDto;
use App\Domain\User\Services\UserService;
use App\Enum\HttpStatus;
use Illuminate\Http\JsonResponse;

class UserController extends ApiController
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    public function index(): JsonResponse
    {
        try {
            $users = $this->userService->listUsers();

            return $this->successResponse(
                array_map(fn ($user) => $user->toArray(), $users),
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUser($id);

            return $this->successResponse($user->toArray());
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function create(CreateUserRequestDto $dto): JsonResponse
    {
        try {
            $createdUser = $this->userService->createUser($dto);

            return $this->successResponse(
                $createdUser->toArray(),
                HttpStatus::HTTP_CREATED,
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function update(int $id, UpdateUserRequestDto $dto): JsonResponse
    {
        try {
            $updatedUser = $this->userService->updateUser($id, $dto);

            return $this->successResponse($updatedUser->toArray());
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);

            return $this->successResponse([], HttpStatus::HTTP_NO_CONTENT);
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }
}
