<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\ExportService;
use App\Services\Analytics\SegmentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ExportController — API для экспорта и сегментации
 * 
 * Endpoints:
 * - POST /api/v2/exports/create (createExport)
 * - GET /api/v2/exports/history (getHistory)
 * - GET /api/v2/segments (getSegments)
 * - GET /api/v2/segments/compare (compareSegments)
 */
final class ExportController extends Controller
{
    public function __construct(
        private readonly ExportService $exportService,
        private readonly SegmentationService $segmentationService,
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

            return response()->json([
                'data' => $export,
                'correlation_id' => (string)$correlationId,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Create export error', ['exception' => $e]);
            return response()->json([
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
                tenantId: auth()->user()->tenant_id,
                limit: $validated['limit'] ?? 50,
                context: ['correlation_id' => (string)$correlationId],
            );

            return response()->json([
                'data' => $history,
                'correlation_id' => (string)$correlationId,
            ]);
        } catch (\Exception $e) {
            \Log::error('Get export history error', ['exception' => $e]);
            return response()->json([
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
                tenantId: auth()->user()->tenant_id,
                criteria: $criteria,
                context: ['correlation_id' => (string)$correlationId],
            );

            return response()->json([
                'data' => $segments,
                'correlation_id' => (string)$correlationId,
            ]);
        } catch (\Exception $e) {
            \Log::error('Get segments error', ['exception' => $e]);
            return response()->json([
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
                tenantId: auth()->user()->tenant_id,
                segment1: $validated['segment_1'],
                segment2: $validated['segment_2'],
                context: ['correlation_id' => (string)$correlationId],
            );

            return response()->json([
                'data' => $comparison,
                'correlation_id' => (string)$correlationId,
            ]);
        } catch (\Exception $e) {
            \Log::error('Compare segments error', ['exception' => $e]);
            return response()->json([
                'error' => $e->getMessage(),
                'correlation_id' => (string)$correlationId,
            ], 500);
        }
    }
}
