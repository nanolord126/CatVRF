<?php declare(strict_types=1);

namespace App\Domains\Consulting\Analytics\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HeatmapController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Create a new HeatmapController instance.
         *
         * @param HeatmapGeneratorService $heatmapGenerator
         * @param HeatmapExportService $exportService
         * @param ScreenshotService $screenshotService
         */
        public function __construct(
            private readonly HeatmapGeneratorService $heatmapGenerator,
            private readonly HeatmapExportService $exportService,
            private readonly ScreenshotService $screenshotService
        ) {
        }

        /**
         * Get geo-heatmap data with optional filtering.
         *
         * @param Request $request
         * @return JsonResponse Heatmap data: {points, stats, cache_info}
         *
         * Query Parameters:
         * - tenant_id (required): Tenant ID for scoping
         * - vertical (optional): Filter by vertical (beauty, food, auto, etc.)
         * - activity_type (optional): Filter by activity type (view, click, purchase, etc.)
         * - from_date (optional): Start date (YYYY-MM-DD)
         * - to_date (optional): End date (YYYY-MM-DD)
         * - correlation_id (optional): Custom correlation ID for tracing
         *
         * Response:
         * {
         *   "points": [
         *     {"lat": 55.7558, "lng": 37.6173, "value": 45},
         *     ...
         *   ],
         *   "stats": {
         *     "point_count": 1234,
         *     "max_value": 100,
         *     "avg_value": 42.5,
         *     "unique_cities": 15
         *   },
         *   "cache_info": {
         *     "cached": true,
         *     "cached_at": "2026-03-23T10:30:00Z",
         *     "expires_at": "2026-03-23T11:30:00Z"
         *   },
         *   "correlation_id": "..."
         * }
         */
        public function geoHeatmap(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? "geo-{$this->generateTraceId()}";

            try {
                // Validate input
                $validated = $request->validate([
                    'tenant_id' => 'required|integer|min:1',
                    'vertical' => 'nullable|string|in:beauty,food,auto,hotels,realestate',
                    'activity_type' => 'nullable|string|in:view,click,purchase,booking,order',
                    'from_date' => 'nullable|date_format:Y-m-d',
                    'to_date' => 'nullable|date_format:Y-m-d',
                ]);

                // Verify tenant authorization
                $this->authorizeForTenant($validated['tenant_id']);

                Log::channel('audit')->info('Geo-heatmap data requested', [
                    'tenant_id' => $validated['tenant_id'],
                    'vertical' => $validated['vertical'] ?? null,
                    'activity_type' => $validated['activity_type'] ?? null,
                    'correlation_id' => $correlationId,
                ]);

                // Generate heatmap data
                $heatmapData = $this->heatmapGenerator->generateGeoHeatmap(
                    tenantId: $validated['tenant_id'],
                    vertical: $validated['vertical'],
                    activityType: $validated['activity_type'],
                    fromDate: $validated['from_date'] ?? null,
                    toDate: $validated['to_date'] ?? null,
                    correlationId: $correlationId
                );

                return response()->json([
                    'points' => $heatmapData['points'] ?? [],
                    'stats' => $heatmapData['stats'] ?? [],
                    'cache_info' => $heatmapData['cache_info'] ?? [],
                    'correlation_id' => $correlationId,
                ]);

            } catch (ValidationException $e) {
                Log::channel('audit')->warning('Geo-heatmap validation failed', [
                    'errors' => $e->errors(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'correlation_id' => $correlationId,
                ], 422);

            } catch (\Exception $e) {
                Log::channel('audit')->error('Geo-heatmap generation failed', [
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'message' => 'Heatmap generation failed',
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Get click-heatmap data for a specific page.
         *
         * @param Request $request
         * @return JsonResponse Click-heatmap data: {clicks, stats, cache_info}
         *
         * Query Parameters:
         * - tenant_id (required): Tenant ID
         * - page_url (required): Page URL to analyze
         * - from_date (optional): Start date
         * - to_date (optional): End date
         * - device_type (optional): Filter by device (mobile, tablet, desktop)
         * - correlation_id (optional): Custom correlation ID
         *
         * Response:
         * {
         *   "clicks": [
         *     {"x": 100, "y": 200, "count": 25, "selector": "a.button", "browser": "Chrome", "device": "mobile"},
         *     ...
         *   ],
         *   "stats": {
         *     "total_clicks": 5000,
         *     "unique_users": 342,
         *     "avg_clicks_per_user": 14.6,
         *     "most_clicked_element": "a.button"
         *   },
         *   "cache_info": { ... },
         *   "correlation_id": "..."
         * }
         */
        public function clickHeatmap(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? "click-{$this->generateTraceId()}";

            try {
                $validated = $request->validate([
                    'tenant_id' => 'required|integer|min:1',
                    'page_url' => 'required|url',
                    'from_date' => 'nullable|date_format:Y-m-d',
                    'to_date' => 'nullable|date_format:Y-m-d',
                    'device_type' => 'nullable|string|in:mobile,tablet,desktop',
                ]);

                $this->authorizeForTenant($validated['tenant_id']);

                Log::channel('audit')->info('Click-heatmap data requested', [
                    'tenant_id' => $validated['tenant_id'],
                    'page_url' => $validated['page_url'],
                    'device_type' => $validated['device_type'] ?? null,
                    'correlation_id' => $correlationId,
                ]);

                $heatmapData = $this->heatmapGenerator->generateClickHeatmap(
                    tenantId: $validated['tenant_id'],
                    pageUrl: $validated['page_url'],
                    deviceType: $validated['device_type'],
                    fromDate: $validated['from_date'] ?? null,
                    toDate: $validated['to_date'] ?? null,
                    correlationId: $correlationId
                );

                return response()->json([
                    'clicks' => $heatmapData['clicks'] ?? [],
                    'stats' => $heatmapData['stats'] ?? [],
                    'cache_info' => $heatmapData['cache_info'] ?? [],
                    'correlation_id' => $correlationId,
                ]);

            } catch (ValidationException $e) {
                Log::channel('audit')->warning('Click-heatmap validation failed', [
                    'errors' => $e->errors(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'correlation_id' => $correlationId,
                ], 422);

            } catch (\Exception $e) {
                Log::channel('audit')->error('Click-heatmap generation failed', [
                    'error_message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'message' => 'Heatmap generation failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Get page screenshot for click-heatmap overlay.
         *
         * Captures or retrieves cached screenshot of a page.
         * Used by click-heatmap component to display page layout.
         *
         * @param Request $request
         * @return JsonResponse Screenshot metadata: {url, path, size, width, height, cached, etc.}
         *
         * Query Parameters:
         * - tenant_id (required): Tenant ID
         * - page_url (required): Page URL to screenshot
         * - correlation_id (optional): Custom correlation ID
         *
         * Response:
         * {
         *   "url": "...",
         *   "path": "screenshots/2026-03-23-...",
         *   "size": 102400,
         *   "width": 1920,
         *   "height": 1080,
         *   "format": "png",
         *   "cached": true,
         *   "captured_at": "2026-03-23T10:30:00Z",
         *   "expires_at": "2026-03-23T11:30:00Z",
         *   "correlation_id": "..."
         * }
         */
        public function getScreenshot(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? "screenshot-{$this->generateTraceId()}";

            try {
                $validated = $request->validate([
                    'tenant_id' => 'required|integer|min:1',
                    'page_url' => 'required|url',
                ]);

                $this->authorizeForTenant($validated['tenant_id']);

                Log::channel('audit')->info('Page screenshot requested', [
                    'tenant_id' => $validated['tenant_id'],
                    'page_url' => $validated['page_url'],
                    'correlation_id' => $correlationId,
                ]);

                $screenshot = $this->screenshotService->capturePageScreenshot(
                    url: $validated['page_url'],
                    tenantId: $validated['tenant_id'],
                    correlationId: $correlationId
                );

                return response()->json([
                    'screenshot' => $screenshot,
                    'correlation_id' => $correlationId,
                ]);

            } catch (ValidationException $e) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'correlation_id' => $correlationId,
                ], 422);

            } catch (\Exception $e) {
                Log::channel('audit')->error('Screenshot capture failed', [
                    'error_message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'message' => 'Screenshot capture failed',
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Export geo-heatmap to PNG.
         *
         * @param Request $request
         * @return JsonResponse Export result: {url, filename, size, expires_at}
         *
         * Body Parameters (JSON):
         * - tenant_id (required): Tenant ID
         * - heatmap_html (required): HTML content of heatmap visualization
         * - metadata (optional): Report metadata (title, date_range, etc.)
         * - correlation_id (optional): Custom correlation ID
         */
        public function exportGeoHeatmapPng(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? "export-{$this->generateTraceId()}";

            try {
                $validated = $request->validate([
                    'tenant_id' => 'required|integer|min:1',
                    'heatmap_html' => 'required|string|min:100',
                    'metadata' => 'nullable|array',
                ]);

                $this->authorizeForTenant($validated['tenant_id']);

                Log::channel('audit')->info('Geo-heatmap PNG export requested', [
                    'tenant_id' => $validated['tenant_id'],
                    'html_length' => \strlen($validated['heatmap_html']),
                    'correlation_id' => $correlationId,
                ]);

                $export = $this->exportService->exportGeoHeatmapToPng(
                    tenantId: $validated['tenant_id'],
                    heatmapHtml: $validated['heatmap_html'],
                    metadata: $validated['metadata'] ?? [],
                    correlationId: $correlationId
                );

                return response()->json([
                    'export' => $export,
                    'correlation_id' => $correlationId,
                ]);

            } catch (\Exception $e) {
                Log::channel('audit')->error('Geo-heatmap PNG export failed', [
                    'error_message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'message' => 'Export failed',
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Export geo-heatmap to PDF.
         *
         * @param Request $request
         * @return JsonResponse Export result with download URL
         *
         * Body Parameters (JSON):
         * - tenant_id (required): Tenant ID
         * - heatmap_html (required): HTML visualization
         * - metadata (required): Report metadata with title
         * - correlation_id (optional): Custom correlation ID
         */
        public function exportGeoHeatmapPdf(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? "export-{$this->generateTraceId()}";

            try {
                $validated = $request->validate([
                    'tenant_id' => 'required|integer|min:1',
                    'heatmap_html' => 'required|string|min:100',
                    'metadata' => 'required|array',
                    'metadata.title' => 'required|string',
                ]);

                $this->authorizeForTenant($validated['tenant_id']);

                $export = $this->exportService->exportGeoHeatmapToPdf(
                    tenantId: $validated['tenant_id'],
                    heatmapHtml: $validated['heatmap_html'],
                    metadata: $validated['metadata'],
                    correlationId: $correlationId
                );

                return response()->json([
                    'export' => $export,
                    'correlation_id' => $correlationId,
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'PDF export failed',
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Export click-heatmap to PNG.
         *
         * @param Request $request
         * @return JsonResponse Export result
         *
         * Body Parameters (JSON):
         * - tenant_id (required): Tenant ID
         * - canvas_data_url (required): Canvas data URL from frontend
         * - metadata (optional): Export metadata
         */
        public function exportClickHeatmapPng(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? "export-{$this->generateTraceId()}";

            try {
                $validated = $request->validate([
                    'tenant_id' => 'required|integer|min:1',
                    'canvas_data_url' => 'required|string|starts_with:data:image/',
                ]);

                $this->authorizeForTenant($validated['tenant_id']);

                $export = $this->exportService->exportClickHeatmapToPng(
                    tenantId: $validated['tenant_id'],
                    canvasDataUrl: $validated['canvas_data_url'],
                    correlationId: $correlationId
                );

                return response()->json([
                    'export' => $export,
                    'correlation_id' => $correlationId,
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Export failed',
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Export click-heatmap to PDF.
         *
         * @param Request $request
         * @return JsonResponse Export result
         *
         * Body Parameters (JSON):
         * - tenant_id (required): Tenant ID
         * - canvas_data_url (required): Canvas data URL
         * - metadata (required): Report metadata with title
         */
        public function exportClickHeatmapPdf(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? "export-{$this->generateTraceId()}";

            try {
                $validated = $request->validate([
                    'tenant_id' => 'required|integer|min:1',
                    'canvas_data_url' => 'required|string|starts_with:data:image/',
                    'metadata' => 'required|array',
                    'metadata.title' => 'required|string',
                ]);

                $this->authorizeForTenant($validated['tenant_id']);

                $export = $this->exportService->exportClickHeatmapToPdf(
                    tenantId: $validated['tenant_id'],
                    canvasDataUrl: $validated['canvas_data_url'],
                    metadata: $validated['metadata'],
                    correlationId: $correlationId
                );

                return response()->json([
                    'export' => $export,
                    'correlation_id' => $correlationId,
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'PDF export failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Authorize request for tenant.
         *
         * Verifies user has access to analytics for this tenant.
         *
         * @param int $tenantId Tenant ID
         * @return void
         *
         * @throws \Illuminate\Auth\Access\AuthorizationException
         */
        private function authorizeForTenant(int $tenantId): void
        {
            // Verify tenant isolation
            $userTenantId = \filament()->getTenant()->id;
            if ($userTenantId !== $tenantId) {
                Log::channel('audit')->warning('Unauthorized tenant access attempt', [
                    'user_tenant_id' => $userTenantId,
                    'requested_tenant_id' => $tenantId,
                ]);

                $this->authorize('view-analytics');
            }
        }

        /**
         * Generate unique trace ID.
         *
         * @return string Trace ID (timestamp-random)
         */
        private function generateTraceId(): string
        {
            return \now()->timestamp . '-' . Str::random(8);
        }
}
