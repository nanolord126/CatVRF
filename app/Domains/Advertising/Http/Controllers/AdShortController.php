<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Http\Controllers;

use App\Domains\Advertising\Application\UseCases\CreateAdShortUseCase;
use App\Domains\Advertising\Domain\Interfaces\AdShortRepositoryInterface;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * AdShort API Controller
 *
 * Handles API endpoints for short video advertisements.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class AdShortController
{
    public function __construct(
        private readonly AdShortRepositoryInterface $repository,
        private readonly CreateAdShortUseCase $createUseCase,
        private readonly FraudControlService $fraudService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:draft,pending_review,active,paused,completed,rejected,cancelled',
            'tenant_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $query = $this->repository->findByTenant(
            $request->input('tenant_id', 0)
        );

        if ($request->has('status')) {
            $query = $query->where('status', $request->input('status'));
        }

        return response()->json([
            'data' => $query->map(fn ($short) => [
                'id' => $short->uuid,
                'title' => $short->title,
                'video_url' => $short->video_url,
                'thumbnail_url' => $short->thumbnail_url,
                'duration_seconds' => $short->duration_seconds,
                'status' => $short->status,
                'budget' => $short->budget,
                'spent' => $short->spent,
                'pricing_model' => $short->pricing_model,
                'start_at' => $short->start_at->toIso8601String(),
                'end_at' => $short->end_at->toIso8601String(),
            ]),
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $short = $this->repository->findByUuid($uuid);

        if ($short === null) {
            return response()->json(['error' => 'Ad short not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $short->uuid,
                'title' => $short->title,
                'video_url' => $short->video_url,
                'thumbnail_url' => $short->thumbnail_url,
                'duration_seconds' => $short->duration_seconds,
                'status' => $short->status,
                'budget' => $short->budget,
                'spent' => $short->spent,
                'pricing_model' => $short->pricing_model,
                'targeting_criteria' => $short->targeting_criteria,
                'start_at' => $short->start_at->toIso8601String(),
                'end_at' => $short->end_at->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'video_url' => 'required|url|max:500',
            'thumbnail_url' => 'required|url|max:500',
            'duration_seconds' => 'required|integer|min:15|max:60',
            'budget' => 'required|integer|min:10000',
            'pricing_model' => 'required|in:cpm,cpc,cpa,cpv',
            'targeting_criteria' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $short = $this->createUseCase->execute(
            tenantId: $request->input('tenant_id'),
            title: $request->input('title'),
            videoUrl: $request->input('video_url'),
            thumbnailUrl: $request->input('thumbnail_url'),
            durationSeconds: $request->input('duration_seconds'),
            budget: $request->input('budget'),
            pricingModel: $request->input('pricing_model'),
            targetingCriteria: $request->input('targeting_criteria', []),
            userId: $request->user()?->id ?? 0,
        );

        return response()->json([
            'data' => [
                'id' => $short->uuid,
                'title' => $short->title,
                'status' => $short->status,
            ],
        ], 201);
    }

    public function activate(string $uuid): JsonResponse
    {
        $short = $this->repository->findByUuid($uuid);

        if ($short === null) {
            return response()->json(['error' => 'Ad short not found'], 404);
        }

        if (!$short->canTransitionTo('active')) {
            return response()->json(['error' => 'Cannot activate ad short'], 400);
        }

        $this->repository->updateStatus($short->id, 'active');

        return response()->json(['message' => 'Ad short activated']);
    }

    public function pause(string $uuid): JsonResponse
    {
        $short = $this->repository->findByUuid($uuid);

        if ($short === null) {
            return response()->json(['error' => 'Ad short not found'], 404);
        }

        if (!$short->canTransitionTo('paused')) {
            return response()->json(['error' => 'Cannot pause ad short'], 400);
        }

        $this->repository->updateStatus($short->id, 'paused');

        return response()->json(['message' => 'Ad short paused']);
    }
}
