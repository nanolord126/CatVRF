<?php declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use App\Domains\Taxi\Models\TaxiVehicle;
use App\Domains\Taxi\Models\TaxiVehicleMaintenance;
use App\Domains\Taxi\Models\TaxiVehicleInspection;
use App\Domains\Taxi\Models\TaxiFleet;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

/**
 * TaxiFleetManagementService - Production-ready fleet management for taxi operations
 * 
 * Features:
 * - Vehicle lifecycle management
 * - Maintenance scheduling and tracking
 * - Inspection management
 * - Odometer tracking
 * - Document management
 * - Cost tracking
 * - Vehicle availability status
 * - Maintenance reminders
 * - Inspection expiry alerts
 */
final readonly class TaxiFleetManagementService
{
    private const MAINTENANCE_INTERVAL_KM = 10000;
    private const INSPECTION_INTERVAL_DAYS = 365;

    public function __construct(
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Add vehicle to fleet
     */
    public function addVehicle(array $data, string $correlationId = null): TaxiVehicle
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->db->transaction(function () use ($data, $correlationId) {
            $vehicle = TaxiVehicle::create([
                'tenant_id' => tenant()->id ?? 1,
                'driver_id' => $data['driver_id'] ?? null,
                'fleet_id' => $data['fleet_id'] ?? null,
                'brand' => $data['brand'],
                'model' => $data['model'],
                'license_plate' => $data['license_plate'],
                'class' => $data['class'] ?? 'economy',
                'year' => $data['year'],
                'color' => $data['color'] ?? null,
                'status' => 'available',
                'documents' => $data['documents'] ?? [],
                'correlation_id' => $correlationId,
                'metadata' => $data['metadata'] ?? [],
                'tags' => array_merge(['taxi', 'vehicle'], $data['tags'] ?? []),
            ]);

            $this->audit->log(
                action: 'taxi_vehicle_added',
                subjectType: TaxiVehicle::class,
                subjectId: $vehicle->id,
                oldValues: [],
                newValues: $vehicle->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Taxi vehicle added to fleet', [
                'correlation_id' => $correlationId,
                'vehicle_uuid' => $vehicle->uuid,
                'license_plate' => $vehicle->license_plate,
            ]);

            return $vehicle;
        });
    }

    /**
     * Schedule maintenance
     */
    public function scheduleMaintenance(int $vehicleId, array $data, string $correlationId = null): TaxiVehicleMaintenance
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->db->transaction(function () use ($vehicleId, $data, $correlationId) {
            $vehicle = TaxiVehicle::findOrFail($vehicleId);
            
            $maintenance = TaxiVehicleMaintenance::create([
                'tenant_id' => tenant()->id ?? 1,
                'vehicle_id' => $vehicleId,
                'fleet_id' => $vehicle->fleet_id,
                'type' => $data['type'],
                'description' => $data['description'],
                'status' => TaxiVehicleMaintenance::STATUS_SCHEDULED,
                'scheduled_date' => Carbon::parse($data['scheduled_date']),
                'cost_kopeki' => $data['cost_kopeki'] ?? 0,
                'odometer_km' => $data['odometer_km'] ?? 0,
                'next_maintenance_date' => isset($data['next_maintenance_date']) 
                    ? Carbon::parse($data['next_maintenance_date']) 
                    : null,
                'next_maintenance_odometer_km' => $data['next_maintenance_odometer_km'] ?? null,
                'documents' => $data['documents'] ?? [],
                'correlation_id' => $correlationId,
                'metadata' => $data['metadata'] ?? [],
                'tags' => array_merge(['taxi', 'maintenance', $data['type']], $data['tags'] ?? []),
            ]);

            $this->audit->log(
                action: 'taxi_maintenance_scheduled',
                subjectType: TaxiVehicleMaintenance::class,
                subjectId: $maintenance->id,
                oldValues: [],
                newValues: $maintenance->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Taxi vehicle maintenance scheduled', [
                'correlation_id' => $correlationId,
                'maintenance_uuid' => $maintenance->uuid,
                'vehicle_id' => $vehicleId,
                'type' => $maintenance->type,
                'scheduled_date' => $maintenance->scheduled_date->toDateString(),
            ]);

            return $maintenance;
        });
    }

    /**
     * Complete maintenance
     */
    public function completeMaintenance(int $maintenanceId, array $data, string $correlationId = null): TaxiVehicleMaintenance
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->db->transaction(function () use ($maintenanceId, $data, $correlationId) {
            $maintenance = TaxiVehicleMaintenance::findOrFail($maintenanceId);
            
            $maintenance->markAsCompleted(
                $data['odometer_km'],
                $data['cost_kopeki'] ?? null
            );

            // Schedule next maintenance if specified
            if (isset($data['schedule_next']) && $data['schedule_next']) {
                $this->scheduleNextMaintenance($maintenance, $data, $correlationId);
            }

            // Update vehicle status if it was in maintenance
            if ($maintenance->vehicle->status === 'maintenance') {
                $maintenance->vehicle->update(['status' => 'available']);
            }

            $this->audit->log(
                action: 'taxi_maintenance_completed',
                subjectType: TaxiVehicleMaintenance::class,
                subjectId: $maintenance->id,
                oldValues: [],
                newValues: $maintenance->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Taxi vehicle maintenance completed', [
                'correlation_id' => $correlationId,
                'maintenance_uuid' => $maintenance->uuid,
                'vehicle_id' => $maintenance->vehicle_id,
                'odometer_km' => $maintenance->odometer_km,
            ]);

            return $maintenance->fresh();
        });
    }

    /**
     * Schedule vehicle inspection
     */
    public function scheduleInspection(int $vehicleId, array $data, string $correlationId = null): TaxiVehicleInspection
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->db->transaction(function () use ($vehicleId, $data, $correlationId) {
            $vehicle = TaxiVehicle::findOrFail($vehicleId);
            
            $inspection = TaxiVehicleInspection::create([
                'tenant_id' => tenant()->id ?? 1,
                'vehicle_id' => $vehicleId,
                'fleet_id' => $vehicle->fleet_id,
                'type' => $data['type'],
                'status' => TaxiVehicleInspection::STATUS_SCHEDULED,
                'inspection_date' => Carbon::parse($data['inspection_date']),
                'expiry_date' => isset($data['expiry_date']) 
                    ? Carbon::parse($data['expiry_date']) 
                    : null,
                'inspector_name' => $data['inspector_name'] ?? null,
                'inspector_license' => $data['inspector_license'] ?? null,
                'documents' => $data['documents'] ?? [],
                'correlation_id' => $correlationId,
                'metadata' => $data['metadata'] ?? [],
                'tags' => array_merge(['taxi', 'inspection', $data['type']], $data['tags'] ?? []),
            ]);

            $this->audit->log(
                action: 'taxi_inspection_scheduled',
                subjectType: TaxiVehicleInspection::class,
                subjectId: $inspection->id,
                oldValues: [],
                newValues: $inspection->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Taxi vehicle inspection scheduled', [
                'correlation_id' => $correlationId,
                'inspection_uuid' => $inspection->uuid,
                'vehicle_id' => $vehicleId,
                'type' => $inspection->type,
                'inspection_date' => $inspection->inspection_date->toDateString(),
            ]);

            return $inspection;
        });
    }

    /**
     * Complete inspection
     */
    public function completeInspection(int $inspectionId, array $data, string $correlationId = null): TaxiVehicleInspection
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->db->transaction(function () use ($inspectionId, $data, $correlationId) {
            $inspection = TaxiVehicleInspection::findOrFail($inspectionId);
            
            $defectsFound = $data['defects_found'] ?? 0;
            $result = $data['result'] ?? TaxiVehicleInspection::RESULT_PASS;
            
            if ($result === TaxiVehicleInspection::RESULT_PASS) {
                $inspection->markAsPassed($result, $defectsFound, $data['next_inspection_date'] ?? null);
            } else {
                $inspection->markAsFailed($defectsFound, $data['failure_reason'] ?? 'Inspection failed');
                
                // Mark vehicle as unavailable if inspection failed
                $inspection->vehicle->update(['status' => 'out_of_service']);
            }

            $this->audit->log(
                action: 'taxi_inspection_completed',
                subjectType: TaxiVehicleInspection::class,
                subjectId: $inspection->id,
                oldValues: [],
                newValues: $inspection->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Taxi vehicle inspection completed', [
                'correlation_id' => $correlationId,
                'inspection_uuid' => $inspection->uuid,
                'vehicle_id' => $inspection->vehicle_id,
                'result' => $result,
                'defects_found' => $defectsFound,
            ]);

            return $inspection->fresh();
        });
    }

    /**
     * Get fleet overview
     */
    public function getFleetOverview(?int $fleetId = null, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $tenantId = tenant()->id ?? 1;
        
        $query = TaxiVehicle::where('tenant_id', $tenantId);
        
        if ($fleetId) {
            $query->where('fleet_id', $fleetId);
        }
        
        $vehicles = $query->get();
        
        $totalVehicles = $vehicles->count();
        $availableVehicles = $vehicles->where('status', 'available')->count();
        $maintenanceVehicles = $vehicles->where('status', 'maintenance')->count();
        $outOfServiceVehicles = $vehicles->where('status', 'out_of_service')->count();
        
        // Get upcoming maintenance
        $upcomingMaintenance = TaxiVehicleMaintenance::where('tenant_id', $tenantId)
            ->where('status', TaxiVehicleMaintenance::STATUS_SCHEDULED)
            ->whereBetween('scheduled_date', [now(), now()->addDays(30)])
            ->when($fleetId, fn($q) => $q->where('fleet_id', $fleetId))
            ->orderBy('scheduled_date')
            ->get();
        
        // Get expiring inspections
        $expiringInspections = TaxiVehicleInspection::where('tenant_id', $tenantId)
            ->where('status', TaxiVehicleInspection::STATUS_PASSED)
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->when($fleetId, fn($q) => $q->where('fleet_id', $fleetId))
            ->orderBy('expiry_date')
            ->get();
        
        // Get overdue maintenance
        $overdueMaintenance = TaxiVehicleMaintenance::where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->where('status', TaxiVehicleMaintenance::STATUS_OVERDUE)
                  ->orWhere(function ($q) {
                      $q->where('status', TaxiVehicleMaintenance::STATUS_SCHEDULED)
                        ->where('scheduled_date', '<', now());
                  });
            })
            ->when($fleetId, fn($q) => $q->where('fleet_id', $fleetId))
            ->get();
        
        return [
            'fleet_id' => $fleetId,
            'vehicles' => [
                'total' => $totalVehicles,
                'available' => $availableVehicles,
                'maintenance' => $maintenanceVehicles,
                'out_of_service' => $outOfServiceVehicles,
                'availability_rate' => $totalVehicles > 0 ? ($availableVehicles / $totalVehicles) * 100 : 0,
            ],
            'maintenance' => [
                'upcoming_count' => $upcomingMaintenance->count(),
                'upcoming' => $upcomingMaintenance->map(function ($m) {
                    return [
                        'uuid' => $m->uuid,
                        'vehicle_id' => $m->vehicle_id,
                        'type' => $m->type,
                        'scheduled_date' => $m->scheduled_date->toDateString(),
                        'days_until' => now()->diffInDays($m->scheduled_date),
                    ];
                })->values(),
                'overdue_count' => $overdueMaintenance->count(),
                'overdue' => $overdueMaintenance->map(function ($m) {
                    return [
                        'uuid' => $m->uuid,
                        'vehicle_id' => $m->vehicle_id,
                        'type' => $m->type,
                        'scheduled_date' => $m->scheduled_date->toDateString(),
                        'days_overdue' => now()->diffInDays($m->scheduled_date),
                    ];
                })->values(),
            ],
            'inspections' => [
                'expiring_count' => $expiringInspections->count(),
                'expiring' => $expiringInspections->map(function ($i) {
                    return [
                        'uuid' => $i->uuid,
                        'vehicle_id' => $i->vehicle_id,
                        'type' => $i->type,
                        'expiry_date' => $i->expiry_date->toDateString(),
                        'days_until_expiry' => $i->getDaysUntilExpiry(),
                    ];
                })->values(),
            ],
        ];
    }

    /**
     * Get vehicle maintenance history
     */
    public function getVehicleMaintenanceHistory(int $vehicleId, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $vehicle = TaxiVehicle::findOrFail($vehicleId);
        
        $maintenanceHistory = TaxiVehicleMaintenance::where('vehicle_id', $vehicleId)
            ->orderBy('scheduled_date', 'desc')
            ->get();
        
        $totalCostKopeki = $maintenanceHistory->where('status', TaxiVehicleMaintenance::STATUS_COMPLETED)
            ->sum('cost_kopeki');
        
        return [
            'vehicle' => [
                'id' => $vehicle->id,
                'uuid' => $vehicle->uuid,
                'license_plate' => $vehicle->license_plate,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
            ],
            'total_cost_rubles' => $totalCostKopeki / 100,
            'total_maintenance_count' => $maintenanceHistory->count(),
            'maintenance_history' => $maintenanceHistory->map(function ($m) {
                return [
                    'uuid' => $m->uuid,
                    'type' => $m->type,
                    'description' => $m->description,
                    'status' => $m->status,
                    'scheduled_date' => $m->scheduled_date->toDateString(),
                    'completed_date' => $m->completed_date?->toDateString(),
                    'cost_rubles' => $m->getCostInRubles(),
                    'odometer_km' => $m->odometer_km,
                ];
            })->values(),
        ];
    }

    /**
     * Schedule next maintenance automatically
     */
    private function scheduleNextMaintenance(TaxiVehicleMaintenance $maintenance, array $data, string $correlationId): void
    {
        $nextOdometer = $maintenance->odometer_km + self::MAINTENANCE_INTERVAL_KM;
        $nextDate = now()->addMonths(3); // Default 3 months
        
        $this->scheduleMaintenance($maintenance->vehicle_id, [
            'type' => $maintenance->type,
            'description' => 'Scheduled maintenance (auto)',
            'scheduled_date' => $nextDate->toDateString(),
            'next_maintenance_odometer_km' => $nextOdometer,
            'cost_kopeki' => $data['estimated_next_cost_kopeki'] ?? 0,
        ], $correlationId);
    }
}
