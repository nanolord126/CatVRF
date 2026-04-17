<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Str;

final class ExportController extends Controller
{


    public function __construct(
            private readonly ExportService $exportService,
            private readonly SegmentationService $segmentationService,
            private readonly LogManager $logger,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {

    }
        /**
         * POST /api/v2/exports/create
         * Создать экспорт данных
         */
        public function createExport(Request $request): JsonResponse {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'data' => 'required|array',
                    'filename' => 'required|string|max:255',
                    'format' => 'required|in:csv,excel,pdf,json',
                ]);
                $export = match ($validated['format']) {
                    'csv' => $this->exportService->exportToCSV(
                        $validated['data'],
                        $validated['filename'],
                        ['correlation_id' => (string)$correlationId],
                    ),
                    'excel' => $this->exportService->exportToExcel(
                        $validated['data'],
                        $validated['filename'],
                        ['correlation_id' => (string)$correlationId],
                    ),
                    'pdf' => $this->exportService->exportToPDF(
                        $validated['data'],
                        $validated['filename'],
                        ['correlation_id' => (string)$correlationId],
                    ),
                    'json' => $this->exportService->exportToJSON(
                        $validated['data'],
                        $validated['filename'],
                        ['correlation_id' => (string)$correlationId],
                    ),
                };
                return $this->response->json([
                    'data' => $export,
                    'correlation_id' => (string)$correlationId,
                ], 201);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('Create export error', ['exception' => $e]);
                return $this->response->json([
                    'error' => $e->getMessage(),
                    'correlation_id' => (string)$correlationId,
                ], 500);
            }
        }
        /**
         * GET /api/v2/exports/history
         * Получить историю экспортов
         */
        public function getHistory(Request $request): JsonResponse {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'limit' => 'nullable|integer|min:1|max:100',
                ]);
                $history = $this->exportService->getExportHistory(
                    tenantId: $this->guard->user()->tenant_id,
                    limit: $validated['limit'] ?? 50,
                    context: ['correlation_id' => (string)$correlationId],
                );
                return $this->response->json([
                    'data' => $history,
                    'correlation_id' => (string)$correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('Get export history error', ['exception' => $e]);
                return $this->response->json([
                    'error' => $e->getMessage(),
                    'correlation_id' => (string)$correlationId,
                ], 500);
            }
        }
        /**
         * GET /api/v2/segments
         * Получить сегменты клиентов
         */
        public function getSegments(Request $request): JsonResponse {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'by_value' => 'nullable|boolean',
                    'by_behavior' => 'nullable|boolean',
                    'by_location' => 'nullable|boolean',
                ]);
                $criteria = array_filter($validated);
                if (empty($criteria)) {
                    $criteria = ['by_value' => true, 'by_behavior' => true];
                }
                $segments = $this->segmentationService->segmentCustomers(
                    tenantId: $this->guard->user()->tenant_id,
                    criteria: $criteria,
                    context: ['correlation_id' => (string)$correlationId],
                );
                return $this->response->json([
                    'data' => $segments,
                    'correlation_id' => (string)$correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('Get segments error', ['exception' => $e]);
                return $this->response->json([
                    'error' => $e->getMessage(),
                    'correlation_id' => (string)$correlationId,
                ], 500);
            }
        }
        /**
         * GET /api/v2/segments/compare
         * Сравнить два сегмента
         */
        public function compareSegments(Request $request): JsonResponse {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'segment_1' => 'required|string',
                    'segment_2' => 'required|string',
                ]);
                $comparison = $this->segmentationService->compareSegments(
                    tenantId: $this->guard->user()->tenant_id,
                    segment1: $validated['segment_1'],
                    segment2: $validated['segment_2'],
                    context: ['correlation_id' => (string)$correlationId],
                );
                return $this->response->json([
                    'data' => $comparison,
                    'correlation_id' => (string)$correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('Compare segments error', ['exception' => $e]);
                return $this->response->json([
                    'error' => $e->getMessage(),
                    'correlation_id' => (string)$correlationId,
                ], 500);
            }
        }
}
