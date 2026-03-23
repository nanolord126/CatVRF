<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Domains\Analytics\Services\CustomMetricService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * API контроллер для пользовательских метрик
 * 
 * Endpoints:
 * - GET /api/analytics/heatmaps/custom/geo
 * - GET /api/analytics/heatmaps/custom/click
 */
final class CustomMetricController
{
    public function __construct(
        private readonly CustomMetricService $customMetricService,
    ) {
    }

    /**
     * GET /api/analytics/heatmaps/custom/geo
     * 
     * Получить кастомную метрику для геоданных
     * 
     * Query parameters:
     * - vertical (required): String, max 50
     * - metric (required): event_intensity|engagement_score|growth_rate|hotspot_concentration|user_retention
     * - from_date (required): YYYY-MM-DD
     * - to_date (required): YYYY-MM-DD, after from_date
     * - aggregation (optional): hourly|daily|weekly, default daily
     * 
     * Response: {data: {...}, correlation_id: "..."}
     */
    public function customGeo(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        
        try {
            $validated = $request->validate([
                'vertical' => 'required|string|max:50',
                'metric' => 'required|in:event_intensity,engagement_score,growth_rate,hotspot_concentration,user_retention',
                'from_date' => 'required|date_format:Y-m-d',
                'to_date' => 'required|date_format:Y-m-d|after:from_date',
                'aggregation' => 'sometimes|in:hourly,daily,weekly',
            ]);

            // Rate limiting
            $tenant = filament()->getTenant();
            $tenantId = $tenant?->id ?? auth()->id() ?? 0;
            $rateLimitKey = "ratelimit:custom:geo:{$tenantId}:{$validated['metric']}";
            
            $count = Cache::increment($rateLimitKey, 1, 60);
            if ($count > 100) {
                Log::channel('fraud_alert')->warning('Rate limit exceeded', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'endpoint' => '/custom/geo',
                    'metric' => $validated['metric'],
                ]);

                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'correlation_id' => $correlationId,
                ], 429)
                    ->header('Retry-After', '60');
            }

            $this->customMetricService->setCorrelationId($correlationId);

            $result = $this->customMetricService->getGeoCustomMetric(
                $tenantId,
                $validated['vertical'],
                Carbon::createFromFormat('Y-m-d', $validated['from_date'])->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $validated['to_date'])->endOfDay(),
                $validated['metric'],
                $validated['aggregation'] ?? 'daily'
            );

            Log::channel('audit')->info('Geo custom metric API called', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'vertical' => $validated['vertical'],
                'metric' => $validated['metric'],
            ]);

            return response()->json([
                'data' => $result,
                'correlation_id' => $correlationId,
            ]);

        } catch (ValidationException $e) {
            Log::channel('error')->warning('Geo custom metric validation failed', [
                'correlation_id' => $correlationId,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
                'correlation_id' => $correlationId,
            ], 422);

        } catch (\Exception $e) {
            Log::channel('error')->error('Geo custom metric API error', [
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
     * GET /api/analytics/heatmaps/custom/click
     * 
     * Получить кастомную метрику для клик-данных
     * 
     * Query parameters:
     * - vertical (required): String, max 50
     * - metric (required): click_density|interaction_score|user_engagement|click_conversion
     * - page_url (required): Valid URL, max 500
     * - from_date (required): YYYY-MM-DD
     * - to_date (required): YYYY-MM-DD, after from_date
     * - aggregation (optional): hourly|daily, default daily
     * 
     * Response: {data: {...}, correlation_id: "..."}
     */
    public function customClick(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        
        try {
            $validated = $request->validate([
                'vertical' => 'required|string|max:50',
                'metric' => 'required|in:click_density,interaction_score,user_engagement,click_conversion',
                'page_url' => 'required|url|max:500',
                'from_date' => 'required|date_format:Y-m-d',
                'to_date' => 'required|date_format:Y-m-d|after:from_date',
                'aggregation' => 'sometimes|in:hourly,daily',
            ]);

            // Rate limiting
            $tenant = filament()->getTenant();
            $tenantId = $tenant?->id ?? auth()->id() ?? 0;
            $urlHash = md5($validated['page_url']);
            $rateLimitKey = "ratelimit:custom:click:{$tenantId}:{$validated['metric']}:{$urlHash}";
            
            $count = Cache::increment($rateLimitKey, 1, 60);
            if ($count > 100) {
                Log::channel('fraud_alert')->warning('Rate limit exceeded', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'endpoint' => '/custom/click',
                    'metric' => $validated['metric'],
                ]);

                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'correlation_id' => $correlationId,
                ], 429)
                    ->header('Retry-After', '60');
            }

            $this->customMetricService->setCorrelationId($correlationId);

            $result = $this->customMetricService->getClickCustomMetric(
                $tenantId,
                $validated['vertical'],
                $validated['page_url'],
                Carbon::createFromFormat('Y-m-d', $validated['from_date'])->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $validated['to_date'])->endOfDay(),
                $validated['metric'],
                $validated['aggregation'] ?? 'daily'
            );

            Log::channel('audit')->info('Click custom metric API called', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'vertical' => $validated['vertical'],
                'metric' => $validated['metric'],
                'page_url' => $validated['page_url'],
            ]);

            return response()->json([
                'data' => $result,
                'correlation_id' => $correlationId,
            ]);

        } catch (ValidationException $e) {
            Log::channel('error')->warning('Click custom metric validation failed', [
                'correlation_id' => $correlationId,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
                'correlation_id' => $correlationId,
            ], 422);

        } catch (\Exception $e) {
            Log::channel('error')->error('Click custom metric API error', [
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
