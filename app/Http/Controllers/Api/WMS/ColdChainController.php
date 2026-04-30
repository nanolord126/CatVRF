<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\WMS;

use App\Services\Compliance\ColdChainMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class ColdChainController
{
    public function __construct(
        private ColdChainMonitoringService $service,
    ) {}

    public function recordTemperature(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|integer',
            'zone_id' => 'required|integer',
            'temperature' => 'required|numeric',
            'humidity' => 'sometimes|numeric',
            'sensor_id' => 'required|string|max:100',
        ]);

        $readingId = $this->service->recordTemperatureReading(
            $validated['warehouse_id'],
            $validated['zone_id'],
            $validated['temperature'],
            $validated['humidity'] ?? null,
            $validated['sensor_id'],
            Auth::user()->tenant_id
        );

        return response()->json([
            'success' => true,
            'reading_id' => $readingId,
        ], 201);
    }

    public function getReadings(Request $request, ?int $warehouseId = null): JsonResponse
    {
        $startDate = $request->get('start', now()->subDays(7)->toDateString());
        $endDate = $request->get('end', now()->toDateString());

        $readings = $this->service->db->table('cold_chain_readings')
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->orderBy('recorded_at', 'desc')
            ->get();

        $sensors = $readings->groupBy('sensor_id')->map(function ($group) {
            $latest = $group->first();
            return [
                'sensor_id' => $latest->sensor_id,
                'zone_id' => $latest->zone_id,
                'temperature' => $latest->temperature,
                'humidity' => $latest->humidity,
                'online' => now()->diffInMinutes($latest->recorded_at) < 5,
                'status' => $this->getSensorStatus($latest->temperature),
            ];
        })->values();

        return response()->json([
            'sensors' => $sensors,
            'temperature' => $readings->map(fn ($r) => [
                'timestamp' => $r->recorded_at,
                'value' => $r->temperature,
            ])->toArray(),
            'humidity' => $readings->map(fn ($r) => [
                'timestamp' => $r->recorded_at,
                'value' => $r->humidity,
            ])->toArray(),
        ]);
    }

    public function getAlerts(Request $request, ?int $warehouseId = null): JsonResponse
    {
        $alerts = $this->service->db->table('cold_chain_alerts')
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->where('status', 'active')
            ->orderBy('started_at', 'desc')
            ->get();

        return response()->json($alerts);
    }

    public function getComplianceReport(Request $request, ?int $warehouseId = null): JsonResponse
    {
        $startDate = $request->get('start', now()->subDays(30)->toDateString());
        $endDate = $request->get('end', now()->toDateString());

        $report = $this->service->generateComplianceReport($warehouseId ?? 1, $startDate, $endDate);

        if ($request->boolean('download')) {
            return response()->json($report)->header('Content-Disposition', 'attachment; filename=compliance-report.json');
        }

        return response()->json($report);
    }

    public function resolveAlert(Request $request, string $alertId): JsonResponse
    {
        $validated = $request->validate([
            'resolution' => 'required|string|max:255',
        ]);

        $resolved = $this->service->resolveAlert($alertId, $validated['resolution'], Auth::id());

        return response()->json(['success' => $resolved]);
    }

    public function escalateAlert(Request $request, string $alertId): JsonResponse
    {
        $escalated = $this->service->escalateAlert($alertId, 'critical');

        return response()->json(['success' => $escalated]);
    }

    private function getSensorStatus(float $temperature): string
    {
        if ($temperature < 2.0 || $temperature > 25.0) {
            return 'warning';
        }
        return 'online';
    }
}
