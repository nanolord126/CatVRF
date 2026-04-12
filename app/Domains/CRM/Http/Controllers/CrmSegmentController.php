<?php

declare(strict_types=1);

namespace App\Domains\CRM\Http\Controllers;

use App\Domains\CRM\DTOs\CreateCrmSegmentDto;
use App\Domains\CRM\Models\CrmSegment;
use App\Domains\CRM\Services\CrmSegmentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * CRM Segment API Controller — управление сегментами.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmSegmentController extends Controller
{
    public function __construct(
        private readonly CrmSegmentationService $segmentationService,
    ) {}

    /**
     * GET /api/v1/crm/segments — список сегментов.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);

        $segments = CrmSegment::query()
            ->where('tenant_id', $tenantId)
            ->withCount('clients')
            ->orderBy('name')
            ->get();

        return new JsonResponse([
            'success' => true,
            'data' => $segments,
            'correlation_id' => $request->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }

    /**
     * POST /api/v1/crm/segments — создать сегмент.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:static,dynamic',
            'conditions' => 'required_if:type,dynamic|array',
            'color' => 'nullable|string|max:20',
        ]);

        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $dto = new CreateCrmSegmentDto(
            tenantId: (int) $validated['tenant_id'],
            correlationId: $correlationId,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            type: $validated['type'],
            conditions: $validated['conditions'] ?? [],
            color: $validated['color'] ?? null,
        );

        $segment = $this->segmentationService->createSegment($dto);

        return new JsonResponse([
            'success' => true,
            'data' => $segment,
            'correlation_id' => $correlationId,
        ], 201);
    }

    /**
     * POST /api/v1/crm/segments/{id}/recalculate — пересчитать сегмент.
     */
    public function recalculate(Request $request, int $id): JsonResponse
    {
        $segment = CrmSegment::query()->findOrFail($id);
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->segmentationService->recalculateSegment($segment, $correlationId);

        return new JsonResponse([
            'success' => true,
            'message' => 'Сегмент пересчитан',
            'data' => $segment->fresh()->loadCount('clients'),
            'correlation_id' => $correlationId,
        ]);
    }
}
