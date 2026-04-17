<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\Services\TaxiFleetManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class TaxiFleetManagementController extends Controller
{
    public function __construct(
        private readonly TaxiFleetManagementService $fleetService,
    ) {}

    public function addVehicle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => 'nullable|integer',
            'fleet_id' => 'nullable|integer',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'license_plate' => 'required|string|max:50',
            'class' => 'nullable|string|in:economy,comfort,business,van',
            'year' => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:100',
            'documents' => 'nullable|array',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $vehicle = $this->fleetService->addVehicle(
            data: $validated,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'vehicle' => $vehicle,
        ]);
    }

    public function scheduleMaintenance(Request $request, int $vehicleId): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:routine,repair,inspection,diagnostic,tire_change,oil_change,brake_service,emergency',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'cost_kopeki' => 'nullable|integer|min:0',
            'odometer_km' => 'nullable|integer|min:0',
            'next_maintenance_date' => 'nullable|date',
            'next_maintenance_odometer_km' => 'nullable|integer|min:0',
            'documents' => 'nullable|array',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $maintenance = $this->fleetService->scheduleMaintenance(
            vehicleId: $vehicleId,
            data: $validated,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'maintenance' => $maintenance,
        ]);
    }

    public function completeMaintenance(Request $request, int $maintenanceId): JsonResponse
    {
        $validated = $request->validate([
            'odometer_km' => 'required|integer|min:0',
            'cost_kopeki' => 'nullable|integer|min:0',
            'schedule_next' => 'nullable|boolean',
            'estimated_next_cost_kopeki' => 'nullable|integer|min:0',
        ]);

        $maintenance = $this->fleetService->completeMaintenance(
            maintenanceId: $maintenanceId,
            data: $validated,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'maintenance' => $maintenance,
        ]);
    }

    public function scheduleInspection(Request $request, int $vehicleId): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:annual,quarterly,pre_trip,post_trip,special',
            'inspection_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'inspector_name' => 'nullable|string|max:255',
            'inspector_license' => 'nullable|string|max:255',
            'documents' => 'nullable|array',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $inspection = $this->fleetService->scheduleInspection(
            vehicleId: $vehicleId,
            data: $validated,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'inspection' => $inspection,
        ]);
    }

    public function completeInspection(Request $request, int $inspectionId): JsonResponse
    {
        $validated = $request->validate([
            'result' => 'required|string|in:pass,fail,conditional',
            'defects_found' => 'nullable|integer|min:0',
            'failure_reason' => 'nullable|string',
            'next_inspection_date' => 'nullable|date',
        ]);

        $inspection = $this->fleetService->completeInspection(
            inspectionId: $inspectionId,
            data: $validated,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'inspection' => $inspection,
        ]);
    }

    public function getFleetOverview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fleet_id' => 'nullable|integer',
        ]);

        $overview = $this->fleetService->getFleetOverview(
            fleetId: $validated['fleet_id'] ?? null,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'overview' => $overview,
        ]);
    }

    public function getVehicleMaintenanceHistory(Request $request, int $vehicleId): JsonResponse
    {
        $history = $this->fleetService->getVehicleMaintenanceHistory(
            vehicleId: $vehicleId,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }
}
