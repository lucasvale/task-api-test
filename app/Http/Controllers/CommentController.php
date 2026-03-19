<?php

namespace App\Http\Controllers;

use App\Application\Comment\DTOs\CreateCommentRequestDto;
use App\Application\Comment\DTOs\UpdateCommentRequestDto;
use App\Domain\Comment\Services\CommentService;
use App\Enum\HttpStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends ApiController
{
    public function __construct(
        private readonly CommentService $commentService
    ) {
    }

    public function index(Request $request, int $taskId): JsonResponse
    {
        try {
            $comments = $this->commentService->listComments($taskId, $request->user()->id);

            return $this->successResponse(
                array_map(fn ($comment) => $comment->toArray(), $comments),
            );
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function show(Request $request, int $taskId, int $id): JsonResponse
    {
        try {
            $comment = $this->commentService->getComment($id, $request->user()->id);

            return $this->successResponse($comment->toArray());
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function create(Request $request, int $taskId, CreateCommentRequestDto $dto): JsonResponse
    {
        try {
            $comment = $this->commentService->createComment($dto, $taskId, $request->user()->id);

            return $this->successResponse(
                $comment->toArray(),
                HttpStatus::HTTP_CREATED,
            );
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function update(Request $request, int $taskId, int $id, UpdateCommentRequestDto $dto): JsonResponse
    {
        try {
            $comment = $this->commentService->updateComment($id, $dto, $request->user()->id);

            return $this->successResponse($comment->toArray());
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function destroy(Request $request, int $taskId, int $id): JsonResponse
    {
        try {
            $this->commentService->deleteComment($id, $request->user()->id);

            return $this->successResponse([], HttpStatus::HTTP_NO_CONTENT);
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }
}
