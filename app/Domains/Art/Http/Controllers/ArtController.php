<?php
declare(strict_types=1);

namespace App\Domains\Art\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Domains\Art\Http\Requests\ArtworkRequest;
use App\Domains\Art\Http\Requests\ProjectRequest;
use App\Domains\Art\Http\Requests\ReviewRequest;
use App\Domains\Art\Models\Project;
use App\Domains\Art\Services\ArtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class ArtController extends Controller
{
    public function __construct(private readonly ArtService $artService, private readonly LoggerInterface $logger)
    {
        $this->middleware(['auth:sanctum', 'tenant', 'throttle:60,1']);
    }

    public function storeProject(ProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();

        try {
            $project = $this->artService->createProject($data);

            return new \Illuminate\Http\JsonResponse([
                'correlation_id' => $data['correlation_id'],
                'project_id' => $project->id,
                'status' => $project->status,
                'mode' => $project->mode,
            ], 201);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to create art project', [
                'correlation_id' => $data['correlation_id'],
                'message' => $exception->getMessage(),
            ]);

            return new \Illuminate\Http\JsonResponse([
                'correlation_id' => $data['correlation_id'],
                'message' => 'Не удалось создать арт-проект',
            ], 422);
        }
    }

    public function storeArtwork(int $projectId, ArtworkRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['project_id'] = $projectId;
        $payload['correlation_id'] = $payload['correlation_id'] ?? (string) Str::uuid();

        try {
            $project = Project::query()->findOrFail($projectId);
            $artwork = $this->artService->addArtwork($project, $payload);

            return new \Illuminate\Http\JsonResponse([
                'correlation_id' => $payload['correlation_id'],
                'artwork_id' => $artwork->id,
                'project_id' => $project->id,
            ], 201);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to add artwork', [
                'correlation_id' => $payload['correlation_id'],
                'message' => $exception->getMessage(),
            ]);

            return new \Illuminate\Http\JsonResponse([
                'correlation_id' => $payload['correlation_id'],
                'message' => 'Не удалось сохранить арт-работу',
            ], 422);
        }
    }

    public function storeReview(int $projectId, ReviewRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['project_id'] = $projectId;
        $payload['correlation_id'] = $payload['correlation_id'] ?? (string) Str::uuid();

        try {
            $project = Project::query()->findOrFail($projectId);
            $review = $this->artService->recordReview($project, $payload);

            return new \Illuminate\Http\JsonResponse([
                'correlation_id' => $payload['correlation_id'],
                'review_id' => $review->id,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to save art review', [
                'correlation_id' => $payload['correlation_id'],
                'message' => $exception->getMessage(),
            ]);

            return new \Illuminate\Http\JsonResponse([
                'correlation_id' => $payload['correlation_id'],
                'message' => 'Не удалось сохранить отзыв',
            ], 422);
        }
    }

}
