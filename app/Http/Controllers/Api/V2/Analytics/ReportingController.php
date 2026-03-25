<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\ReportingService;
use App\Services\Analytics\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ReportingController — API для управления отчётами и экспортами
 * 
 * Endpoints:
 * - POST /api/v2/reporting/schedule (scheduleReport)
 * - GET /api/v2/reporting/scheduled (getScheduledReports)
 * - PUT /api/v2/reporting/{id}/schedule (updateSchedule)
 * - DELETE /api/v2/reporting/{id} (deleteSchedule)
 * - GET /api/v2/reporting/generate (generateReport)
 */
final class ReportingController extends Controller
{
    public function __construct(
        private readonly ReportingService $reportingService,
        private readonly ExportService $exportService,
    ) {
    }

    /**
     * POST /api/v2/reporting/schedule
     * Запланировать отчёт
     */
    public function scheduleReport(Request $request): JsonResponse {
        $correlationId = Str::uuid()->toString();

        try {
            $validated = $request->validate([
                'report_type' => 'required|in:revenue_report,performance_report,customer_report',
                'frequency' => 'required|in:daily,weekly,monthly',
                'recipients' => 'required|array|min:1',
                'recipients.*' => 'email',
            ]);

            $schedule = $this->reportingService->scheduleReport(
                tenantId: auth()->user()->tenant_id,
                reportType: $validated['report_type'],
                frequency: $validated['frequency'],
                recipients: $validated['recipients'],
                context: ['correlation_id' => (string)$correlationId],
            );

            return response()->json([
                'data' => $schedule,
                'correlation_id' => (string)$correlationId,
            ], 201);
        } catch (\Exception $e) {
            \$this->log->error('Schedule report error', ['exception' => $e]);
            return response()->json([
                'error' => $e->getMessage(),
                'correlation_id' => (string)$correlationId,
            ], 500);
        }
    }

    /**
     * GET /api/v2/reporting/scheduled
     * Получить запланированные отчёты
     */
    public function getScheduledReports(Request $request): JsonResponse {
        $correlationId = Str::uuid()->toString();

        try {
            $schedules = $this->reportingService->getScheduledReports(
                tenantId: auth()->user()->tenant_id,
                context: ['correlation_id' => (string)$correlationId],
            );

            return response()->json([
                'data' => $schedules,
                'correlation_id' => (string)$correlationId,
            ]);
        } catch (\Exception $e) {
            \$this->log->error('Get scheduled reports error', ['exception' => $e]);
            return response()->json([
                'error' => $e->getMessage(),
                'correlation_id' => (string)$correlationId,
            ], 500);
        }
    }

    /**
     * PUT /api/v2/reporting/{id}/schedule
     * Обновить расписание отчёта
     */
    public function updateSchedule(Request $request, string $reportId): JsonResponse {
        $correlationId = Str::uuid()->toString();

        try {
            $validated = $request->validate([
                'frequency' => 'required|in:daily,weekly,monthly',
                'recipients' => 'required|array|min:1',
                'recipients.*' => 'email',
            ]);

            $schedule = $this->reportingService->updateReportSchedule(
                reportId: $reportId,
                tenantId: auth()->user()->tenant_id,
                updates: $validated,
                context: ['correlation_id' => (string)$correlationId],
            );

            return response()->json([
                'data' => $schedule,
                'correlation_id' => (string)$correlationId,
            ]);
        } catch (\Exception $e) {
            \$this->log->error('Update report schedule error', ['exception' => $e]);
            return response()->json([
                'error' => $e->getMessage(),
                'correlation_id' => (string)$correlationId,
            ], 500);
        }
    }

    /**
     * DELETE /api/v2/reporting/{id}
     * Удалить расписание отчёта
     */
    public function deleteSchedule(string $reportId): JsonResponse {
        $correlationId = Str::uuid()->toString();

        try {
            $this->reportingService->deleteReportSchedule(
                reportId: $reportId,
                tenantId: auth()->user()->tenant_id,
                context: ['correlation_id' => (string)$correlationId],
            );

            return response()->json([
                'message' => 'Report schedule deleted',
                'correlation_id' => (string)$correlationId,
            ]);
        } catch (\Exception $e) {
            \$this->log->error('Delete report schedule error', ['exception' => $e]);
            return response()->json([
                'error' => $e->getMessage(),
                'correlation_id' => (string)$correlationId,
            ], 500);
        }
    }

    /**
     * GET /api/v2/reporting/generate
     * Сгенерировать отчёт
     */
    public function generateReport(Request $request): JsonResponse {
        $correlationId = Str::uuid()->toString();

        try {
            $validated = $request->validate([
                'report_type' => 'required|in:revenue_report,performance_report,customer_report',
                'date_range' => 'nullable|in:7_days,30_days,90_days',
                'export_format' => 'nullable|in:json,csv,excel,pdf',
            ]);

            $report = $this->reportingService->generateReport(
                tenantId: auth()->user()->tenant_id,
                reportType: $validated['report_type'],
                dateRange: $validated['date_range'] ?? '30_days',
                context: ['correlation_id' => (string)$correlationId],
            );

            // Если запрошен экспорт
            if (isset($validated['export_format'])) {
                $export = $this->exportService->exportToJSON(
                    $report,
                    $validated['report_type'],
                    ['correlation_id' => (string)$correlationId],
                );

                return response()->json([
                    'data' => $report,
                    'export' => $export,
                    'correlation_id' => (string)$correlationId,
                ]);
            }

            return response()->json([
                'data' => $report,
                'correlation_id' => (string)$correlationId,
            ]);
        } catch (\Exception $e) {
            \$this->log->error('Generate report error', ['exception' => $e]);
            return response()->json([
                'error' => $e->getMessage(),
                'correlation_id' => (string)$correlationId,
            ], 500);
        }
    }
}
