<?php

namespace App\Http\Controllers;

use App\Application\Auth\DTOs\LoginRequestDto;
use App\Domain\Auth\Services\AuthService;
use App\Enum\HttpStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    public function login(LoginRequestDto $dto): JsonResponse
    {
        try {
            $result = $this->authService->login($dto);

            return $this->successResponse($result);
        } catch (\RuntimeException $exception) {
            return $this->failResponse(
                ['email' => [$exception->getMessage()]],
                HttpStatus::HTTP_UNAUTHORIZED,
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user()->id);

            return $this->successResponse([], HttpStatus::HTTP_NO_CONTENT);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
