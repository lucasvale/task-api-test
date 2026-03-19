<?php

namespace App\Http\Controllers;

use App\Application\Project\DTOs\CreateProjectRequestDto;
use App\Application\Project\DTOs\UpdateProjectRequestDto;
use App\Domain\Project\Services\ProjectService;
use App\Enum\HttpStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends ApiController
{
    public function __construct(
        private readonly ProjectService $projectService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $projects = $this->projectService->listProjects($request->user()->id);

            return $this->successResponse(
                array_map(fn ($project) => $project->toArray(), $projects),
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $project = $this->projectService->getProject($id, $request->user()->id);

            return $this->successResponse($project->toArray());
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function create(Request $request, CreateProjectRequestDto $dto): JsonResponse
    {
        try {
            $project = $this->projectService->createProject($dto, $request->user()->id);

            return $this->successResponse(
                $project->toArray(),
                HttpStatus::HTTP_CREATED,
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function update(Request $request, int $id, UpdateProjectRequestDto $dto): JsonResponse
    {
        try {
            $project = $this->projectService->updateProject($id, $dto, $request->user()->id);

            return $this->successResponse($project->toArray());
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $this->projectService->deleteProject($id, $request->user()->id);

            return $this->successResponse([], HttpStatus::HTTP_NO_CONTENT);
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), httpStatus: HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }
}
