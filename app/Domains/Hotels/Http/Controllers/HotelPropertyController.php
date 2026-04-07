<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use App\Domains\Hotels\Services\HotelPropertyService;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Контроллер для управления объектами размещения (Hotel Properties).
 *
 * Layer 4: Controllers — CatVRF 2026.
 * Tenant-scoping, fraud-check, correlation_id обязательны.
 *
 * @package App\Domains\Hotels\Http\Controllers
 */
final class HotelPropertyController extends Controller
{
    public function __construct(
        private readonly HotelPropertyService $service,
        private readonly FraudControlService $fraud,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Получить список объектов размещения текущего tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->user()?->tenant_id;
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $properties = $this->service->listProperties($tenantId, $correlationId);

        $this->logger->info('Hotel properties listed', [
            'tenant_id' => $tenantId,
            'count' => $properties->count(),
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse(['data' => $properties]);
    }

    /**
     * Показать конкретный объект размещения.
     */
    public function show(Request $request, int $propertyId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $property = $this->service->getProperty($propertyId, $correlationId);

        $this->logger->info('Hotel property viewed', [
            'property_id' => $propertyId,
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse(['data' => $property]);
    }

    /**
     * Создать новый объект размещения.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = (int) $request->user()?->tenant_id;
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $fraudResult = $this->fraud->check(
            userId: (int) ($request->user()?->id ?? 0),
            operationType: 'hotel_property_create',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        if (($fraudResult['decision'] ?? '') === 'block') {
            return new JsonResponse(['error' => 'Operation blocked by security'], 403);
        }

        $property = $this->service->createProperty(
            data: $request->only(['name', 'address', 'geo_point', 'star_rating']),
            tenantId: $tenantId,
            correlationId: $correlationId,
        );

        $this->logger->info('Hotel property created', [
            'property_id' => $property->id,
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse(['data' => $property], 201);
    }

    /**
     * Обновить объект размещения.
     */
    public function update(Request $request, int $propertyId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $fraudResult = $this->fraud->check(
            userId: (int) ($request->user()?->id ?? 0),
            operationType: 'hotel_property_update',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        if (($fraudResult['decision'] ?? '') === 'block') {
            return new JsonResponse(['error' => 'Operation blocked by security'], 403);
        }

        $property = $this->service->updateProperty(
            propertyId: $propertyId,
            data: $request->only(['name', 'address', 'star_rating']),
            correlationId: $correlationId,
        );

        $this->logger->info('Hotel property updated', [
            'property_id' => $propertyId,
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse(['data' => $property]);
    }

    /**
     * Удалить объект размещения.
     */
    public function destroy(Request $request, int $propertyId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $fraudResult = $this->fraud->check(
            userId: (int) ($request->user()?->id ?? 0),
            operationType: 'hotel_property_delete',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        if (($fraudResult['decision'] ?? '') === 'block') {
            return new JsonResponse(['error' => 'Operation blocked by security'], 403);
        }

        $this->service->deleteProperty($propertyId, $correlationId);

        $this->logger->info('Hotel property deleted', [
            'property_id' => $propertyId,
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse(null, 204);
    }
}
