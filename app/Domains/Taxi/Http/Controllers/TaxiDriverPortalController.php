<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\Services\TaxiDriverPortalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Carbon\Carbon;

final class TaxiDriverPortalController extends Controller
{
    public function __construct(
        private readonly TaxiDriverPortalService $driverPortalService,
    ) {}

    public function getDashboard(Request $request, int $driverId): JsonResponse
    {
        $dashboard = $this->driverPortalService->getDriverDashboard(
            driverId: $driverId,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'dashboard' => $dashboard,
        ]);
    }

    public function getEarnings(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $earnings = $this->driverPortalService->getDriverEarnings(
            driverId: $driverId,
            startDate: Carbon::parse($validated['start_date']),
            endDate: Carbon::parse($validated['end_date']),
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'earnings' => $earnings,
        ]);
    }

    public function createSchedule(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s',
            'break_start_time' => 'nullable|date_format:Y-m-d H:i:s',
            'break_end_time' => 'nullable|date_format:Y-m-d H:i:s',
            'target_rides' => 'nullable|integer|min:1',
            'target_earnings_rubles' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $schedule = $this->driverPortalService->createSchedule(
            driverId: $driverId,
            data: $validated,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'schedule' => $schedule,
        ]);
    }

    public function uploadDocument(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:driver_license,vehicle_registration,insurance,inspection,background_check,medical_certificate,taxi_license,identity_document,contract',
            'document_number' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'issuing_authority' => 'nullable|string|max:255',
            'file_path' => 'required|string',
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer',
            'file_mime_type' => 'required|string|max:100',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $document = $this->driverPortalService->uploadDocument(
            driverId: $driverId,
            data: $validated,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'document' => $document,
        ]);
    }

    public function getDocuments(Request $request, int $driverId): JsonResponse
    {
        $documents = $this->driverPortalService->getDriverDocuments(
            driverId: $driverId,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'documents' => $documents,
        ]);
    }

    public function getRideHistory(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $history = $this->driverPortalService->getDriverRideHistory(
            driverId: $driverId,
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
            perPage: $validated['per_page'] ?? 20,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    public function toggleAvailability(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'available' => 'required|boolean',
        ]);

        $driver = $this->driverPortalService->toggleAvailability(
            driverId: $driverId,
            available: $validated['available'],
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'driver' => $driver,
        ]);
    }

    public function getPerformanceReport(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $report = $this->driverPortalService->getDriverPerformanceReport(
            driverId: $driverId,
            startDate: Carbon::parse($validated['start_date']),
            endDate: Carbon::parse($validated['end_date']),
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }
}
