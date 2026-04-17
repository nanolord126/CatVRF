<?php declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class HeatmapController extends Controller
{

    private readonly HeatmapGeneratorService $heatmapService;
        public function __construct(HeatmapGeneratorService $heatmapService,
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    )
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
                if (!Gate::allows('view_heatmaps')) {
                    $this->logger->channel('audit')->warning('Доступ к тепловой карте запрещён', [
                        'correlation_id' => (string)$correlationId,
                        'user_id' => $this->guard->id(),
                    ]);
                    return $this->response->json([
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
                $this->logger->channel('audit')->info('Гео-тепловая карта запрошена', [
                    'correlation_id' => (string)$correlationId,
                    'user_id' => $this->guard->id(),
                    'tenant_id' => $validated['tenant_id'],
                ]);
                return $this->response->json([
                    'correlation_id' => (string)$correlationId,
                    'heatmap' => $heatmap,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Ошибка при получении гео-тепловой карты', [
                    'correlation_id' => (string)$correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
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
                if (!Gate::allows('view_heatmaps')) {
                    $this->logger->channel('audit')->warning('Доступ к клик-карте запрещён', [
                        'correlation_id' => (string)$correlationId,
                        'user_id' => $this->guard->id(),
                    ]);
                    return $this->response->json([
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
                $this->logger->channel('audit')->info('Клик-тепловая карта запрошена', [
                    'correlation_id' => (string)$correlationId,
                    'user_id' => $this->guard->id(),
                    'page_url' => $validated['page_url'],
                ]);
                return $this->response->json([
                    'correlation_id' => (string)$correlationId,
                    'heatmap' => $heatmap,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Ошибка при получении клик-тепловой карты', [
                    'correlation_id' => (string)$correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'message' => 'Ошибка при генерации карты',
                    'correlation_id' => (string)$correlationId,
                ], 500);
            }
        }
}
