<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Services\BeautySalonService;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * API-контроллер для работы с салонами красоты.
 *
 * CANON 2026: Контроллер делегирует всю бизнес-логику сервису.
 * Никаких прямых запросов к БД, никаких хелперов.
 */
final class BeautySalonController extends Controller
{
    public function __construct(
        private BeautySalonService $salonService,
        private FraudControlService $fraud,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Список салонов (GET /beauty/salons).
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID')
            ?? Str::uuid()->toString();

        try {
            $salons = BeautySalon::query()
                ->where('is_active', true)
                ->with(['masters', 'services'])
                ->paginate(20);

            return new JsonResponse([
                'success' => true,
                'data' => $salons,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('API Error: List Salons Failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Не удалось загрузить список салонов.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Создать новый салон (POST /beauty/salons).
     *
     * CANON 2026: FraudControlService::check() + DB::transaction()
     * перед каждой мутацией.
     */
    public function store(CreateBeautySalonRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID')
            ?? Str::uuid()->toString();

        $tenantId = tenant()->id;

        if ($tenantId === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Tenant not resolved.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $this->fraud->check(
                userId: (int) $request->user()?->id,
                operationType: 'beauty.salon.create',
                amount: 0,
                correlationId: $correlationId,
            );

            $salon = $this->salonService->create(
                array_merge($request->validated(), [
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                ]),
            );

            $this->logger->info('API Success: Beauty Salon Created', [
                'salon_id' => $salon->id,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => $salon,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $this->logger->error('API Error: Create Salon Failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Ошибка при создании салона.',
                'correlation_id' => $correlationId,
            ], 400);
        }
    }

    /**
     * Информация о салоне (GET /beauty/salons/{salon}).
     */
    public function show(\Illuminate\Http\Request $request, BeautySalon $salon): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID')
            ?? Str::uuid()->toString();

        return new JsonResponse([
            'success' => true,
            'data' => $salon->load(['masters', 'services', 'reviews']),
            'correlation_id' => $correlationId,
        ]);
    }
}
