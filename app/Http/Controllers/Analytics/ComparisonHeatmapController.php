<?php
declare(strict_types=1);
namespace App\Http\Controllers\Analytics;
use App\Domains\Consulting\Analytics\Services\ComparisonHeatmapService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
/**
 * API контроллер для сравнения двух периодов
 * 
 * Endpoints:
 * - GET /api/analytics/heatmaps/compare/geo
 * - GET /api/analytics/heatmaps/compare/click
 */
final class ComparisonHeatmapController
{
    public function __construct(
        private readonly ComparisonHeatmapService $comparisonService,
    ) {
    }
    /**
     * GET /api/analytics/heatmaps/compare/geo
     * 
     * Сравнить два периода по геоданным
     * 
     * Query parameters:
     * - vertical (required): String, max 50 - название вертикали
     * - period1_from (required): YYYY-MM-DD
     * - period1_to (required): YYYY-MM-DD, after period1_from
     * - period2_from (required): YYYY-MM-DD
     * - period2_to (required): YYYY-MM-DD, after period2_from
     * - metric (optional): event_count|unique_users|unique_sessions, default event_count
     * 
     * Response: {data: {...}, correlation_id: "..."}
     * Errors: 422 (validation), 429 (rate limit), 500 (server error)
     */
    public function compareGeo(Request $request): JsonResponse
    {
        // Генерировать или получить correlation ID
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        try {
            // Валидировать параметры
            $validated = $request->validate([
                'vertical' => 'required|string|max:50',
                'period1_from' => 'required|date_format:Y-m-d',
                'period1_to' => 'required|date_format:Y-m-d|after:period1_from',
                'period2_from' => 'required|date_format:Y-m-d',
                'period2_to' => 'required|date_format:Y-m-d|after:period2_from',
                'metric' => 'sometimes|in:event_count,unique_users,unique_sessions',
            ]);
            // Rate limiting (100 req/min per tenant)
            $tenant = filament()->getTenant();
            $tenantId = $tenant?->id ?? auth()->id() ?? 0;
            $rateLimitKey = "ratelimit:compare:geo:{$tenantId}:{$validated['vertical']}";
            $count = Cache::increment($rateLimitKey, 1, 60);
            if ($count > 100) {
                Log::channel('fraud_alert')->warning('Rate limit exceeded', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'endpoint' => '/compare/geo',
                    'requests' => $count,
                ]);
                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'correlation_id' => $correlationId,
                ], 429)
                    ->header('Retry-After', '60');
            }
            // Извлечь параметры
            $vertical = $validated['vertical'];
            $period1From = Carbon::createFromFormat('Y-m-d', $validated['period1_from'])->startOfDay();
            $period1To = Carbon::createFromFormat('Y-m-d', $validated['period1_to'])->endOfDay();
            $period2From = Carbon::createFromFormat('Y-m-d', $validated['period2_from'])->startOfDay();
            $period2To = Carbon::createFromFormat('Y-m-d', $validated['period2_to'])->endOfDay();
            $metric = $validated['metric'] ?? 'event_count';
            // Установить correlation ID
            $this->comparisonService->setCorrelationId($correlationId);
            // Получить сравнение
            $result = $this->comparisonService->compareGeoTimeSeries(
                $tenantId,
                $vertical,
                $period1From,
                $period1To,
                $period2From,
                $period2To,
                $metric
            );
            Log::channel('audit')->info('Geo comparison API called', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'metric' => $metric,
                'period1_days' => $period1From->diffInDays($period1To),
                'period2_days' => $period2From->diffInDays($period2To),
            ]);
            return response()->json([
                'data' => $result,
                'correlation_id' => $correlationId,
            ]);
        } catch (ValidationException $e) {
            Log::channel('error')->warning('Geo comparison validation failed', [
                'correlation_id' => $correlationId,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
                'correlation_id' => $correlationId,
            ], 422);
        } catch (\Exception $e) {
            Log::channel('error')->error('Geo comparison API error', [
                'correlation_id' => $correlationId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
    /**
     * GET /api/analytics/heatmaps/compare/click
     * 
     * Сравнить два периода по клик-данным
     * 
     * Query parameters:
     * - vertical (required): String, max 50
     * - page_url (required): Valid URL, max 500
     * - period1_from (required): YYYY-MM-DD
     * - period1_to (required): YYYY-MM-DD, after period1_from
     * - period2_from (required): YYYY-MM-DD
     * - period2_to (required): YYYY-MM-DD, after period2_from
     * 
     * Response: {data: {...}, correlation_id: "..."}
     */
    public function compareClick(Request $request): JsonResponse
    {
        // Генерировать или получить correlation ID
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        try {
            // Валидировать параметры
            $validated = $request->validate([
                'vertical' => 'required|string|max:50',
                'page_url' => 'required|url|max:500',
                'period1_from' => 'required|date_format:Y-m-d',
                'period1_to' => 'required|date_format:Y-m-d|after:period1_from',
                'period2_from' => 'required|date_format:Y-m-d',
                'period2_to' => 'required|date_format:Y-m-d|after:period2_from',
            ]);
            // Rate limiting
            $tenant = filament()->getTenant();
            $tenantId = $tenant?->id ?? auth()->id() ?? 0;
            $pageUrlHash = md5($validated['page_url']);
            $rateLimitKey = "ratelimit:compare:click:{$tenantId}:{$pageUrlHash}";
            $count = Cache::increment($rateLimitKey, 1, 60);
            if ($count > 100) {
                Log::channel('fraud_alert')->warning('Rate limit exceeded', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'endpoint' => '/compare/click',
                    'requests' => $count,
                ]);
                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'correlation_id' => $correlationId,
                ], 429)
                    ->header('Retry-After', '60');
            }
            // Извлечь параметры
            $vertical = $validated['vertical'];
            $pageUrl = $validated['page_url'];
            $period1From = Carbon::createFromFormat('Y-m-d', $validated['period1_from'])->startOfDay();
            $period1To = Carbon::createFromFormat('Y-m-d', $validated['period1_to'])->endOfDay();
            $period2From = Carbon::createFromFormat('Y-m-d', $validated['period2_from'])->startOfDay();
            $period2To = Carbon::createFromFormat('Y-m-d', $validated['period2_to'])->endOfDay();
            // Установить correlation ID
            $this->comparisonService->setCorrelationId($correlationId);
            // Получить сравнение
            $result = $this->comparisonService->compareClickTimeSeries(
                $tenantId,
                $vertical,
                $pageUrl,
                $period1From,
                $period1To,
                $period2From,
                $period2To
            );
            Log::channel('audit')->info('Click comparison API called', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'page_url' => $pageUrl,
                'period1_days' => $period1From->diffInDays($period1To),
                'period2_days' => $period2From->diffInDays($period2To),
            ]);
            return response()->json([
                'data' => $result,
                'correlation_id' => $correlationId,
            ]);
        } catch (ValidationException $e) {
            Log::channel('error')->warning('Click comparison validation failed', [
                'correlation_id' => $correlationId,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
                'correlation_id' => $correlationId,
            ], 422);
        } catch (\Exception $e) {
            Log::channel('error')->error('Click comparison API error', [
                'correlation_id' => $correlationId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
