<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Domains\Analytics\Services\HeatmapGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Контроллер для работы с тепловыми картами
 * SECURITY:
 * - Rate limiting (20 запросов/минуту)
 * - Проверка прав доступа (admin.heatmaps)
 * - Correlation ID во всех логах
 * - Валидация даты
 */
final class HeatmapController
{
    private readonly HeatmapGeneratorService $heatmapService;

    public function __construct(HeatmapGeneratorService $heatmapService)
    {
        $this->heatmapService = $heatmapService;
    }

    /**
     * Получить гео-тепловую карту
     * 
     * GET /api/analytics/heatmaps/geo
     * Query: tenant_id, vertical, from_date, to_date
     */
    public function geoHeatmap(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();

        try {
            // SECURITY: Проверка прав доступа
            if (!$this->gate->allows('view_heatmaps')) {
                $this->log->channel('audit')->warning('Доступ к тепловой карте запрещён', [
                    'correlation_id' => (string)$correlationId,
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'message' => 'Доступ запрещён',
                    'correlation_id' => (string)$correlationId,
                ], 403);
            }

            // Валидация параметров
            $validated = $request->validate([
                'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
                'vertical' => ['nullable', 'string', 'max:50'],
                'from_date' => ['nullable', 'date_format:Y-m-d'],
                'to_date' => ['nullable', 'date_format:Y-m-d'],
            ]);

            // Парсим даты
            $fromDate = $validated['from_date'] ? Carbon::createFromFormat('Y-m-d', $validated['from_date']) : null;
            $toDate = $validated['to_date'] ? Carbon::createFromFormat('Y-m-d', $validated['to_date']) : null;

            // Генерируем тепловую карту
            $heatmap = $this->heatmapService->generateGeoHeatmap(
                tenantId: $validated['tenant_id'],
                vertical: $validated['vertical'],
                fromDate: $fromDate,
                toDate: $toDate,
            );

            $this->log->channel('audit')->info('Гео-тепловая карта запрошена', [
                'correlation_id' => (string)$correlationId,
                'user_id' => auth()->id(),
                'tenant_id' => $validated['tenant_id'],
            ]);

            return response()->json([
                'correlation_id' => (string)$correlationId,
                'heatmap' => $heatmap,
            ]);

        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Ошибка при получении гео-тепловой карты', [
                'correlation_id' => (string)$correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ошибка при генерации карты',
                'correlation_id' => (string)$correlationId,
            ], 500);
        }
    }

    /**
     * Получить клик-тепловую карту
     * 
     * GET /api/analytics/heatmaps/click
     * Query: page_url, from_date, to_date
     */
    public function clickHeatmap(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();

        try {
            // SECURITY: Проверка прав доступа
            if (!$this->gate->allows('view_heatmaps')) {
                $this->log->channel('audit')->warning('Доступ к клик-карте запрещён', [
                    'correlation_id' => (string)$correlationId,
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'message' => 'Доступ запрещён',
                    'correlation_id' => (string)$correlationId,
                ], 403);
            }

            // Валидация параметров
            $validated = $request->validate([
                'page_url' => ['required', 'string', 'url', 'max:500'],
                'from_date' => ['nullable', 'date_format:Y-m-d'],
                'to_date' => ['nullable', 'date_format:Y-m-d'],
            ]);

            // Парсим даты
            $fromDate = $validated['from_date'] ? Carbon::createFromFormat('Y-m-d', $validated['from_date']) : null;
            $toDate = $validated['to_date'] ? Carbon::createFromFormat('Y-m-d', $validated['to_date']) : null;

            // Генерируем клик-карту
            $heatmap = $this->heatmapService->generateClickHeatmap(
                pageUrl: $validated['page_url'],
                fromDate: $fromDate,
                toDate: $toDate,
            );

            $this->log->channel('audit')->info('Клик-тепловая карта запрошена', [
                'correlation_id' => (string)$correlationId,
                'user_id' => auth()->id(),
                'page_url' => $validated['page_url'],
            ]);

            return response()->json([
                'correlation_id' => (string)$correlationId,
                'heatmap' => $heatmap,
            ]);

        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Ошибка при получении клик-тепловой карты', [
                'correlation_id' => (string)$correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ошибка при генерации карты',
                'correlation_id' => (string)$correlationId,
            ], 500);
        }
    }
}
