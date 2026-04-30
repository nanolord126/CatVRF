<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Quality Control Service
 *
 * Manages quality control processes:
 * - QC inspection creation
 * - Inspection results recording
 * - Quarantine management
 * - Quality metrics tracking
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryQualityControlService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create QC inspection
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $batchId  Batch ID (optional)
     * @param  string  $inspectionType  Inspection type
     * @param  int  $sampleSize  Sample size
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Inspection ID
     */
    public function createInspection(
        int $inventoryItemId,
        ?int $batchId,
        string $inspectionType,
        int $sampleSize,
        int $warehouseId,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $inventoryItemId,
            $batchId,
            $inspectionType,
            $sampleSize,
            $warehouseId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $inspectionId = $this->db->table('inventory_qc_inspections')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'inspection_number' => $this->generateInspectionNumber(),
                'inventory_item_id' => $inventoryItemId,
                'batch_id' => $batchId,
                'inspection_type' => $inspectionType,
                'sample_size' => $sampleSize,
                'warehouse_id' => $warehouseId,
                'status' => 'pending',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logCreated(
                entityType: 'InventoryQCInspection',
                entityId: $inspectionId,
                context: [
                    'correlation_id' => $correlationId,
                    'inspection_number' => $this->generateInspectionNumber(),
                    'inspection_type' => $inspectionType,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $inspectionId;
        });
    }

    /**
     * Record inspection results
     *
     * @param  int  $inspectionId  Inspection ID
     * @param  string  $result  Result (pass/fail/conditional)
     * @param  array<array<string, mixed>>  $defects  Defects found
     * @param  string|null  $notes  Notes
     * @param  int  $inspectorId  Inspector ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function recordInspectionResults(
        int $inspectionId,
        string $result,
        array $defects,
        ?string $notes,
        int $inspectorId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $inspectionId,
            $result,
            $defects,
            $notes,
            $inspectorId,
            $tenantId,
            $correlationId
        ) {
            $inspection = $this->db->table('inventory_qc_inspections')
                ->where('id', $inspectionId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $inspection) {
                throw new \RuntimeException("Pending inspection {$inspectionId} not found");
            }

            $this->db->table('inventory_qc_inspections')
                ->where('id', $inspectionId)
                ->update([
                    'status' => 'completed',
                    'result' => $result,
                    'defects_count' => count($defects),
                    'defects' => ! empty($defects) ? json_encode($defects) : null,
                    'notes' => $notes,
                    'inspected_by' => $inspectorId,
                    'inspected_at' => now(),
                ]);

            foreach ($defects as $defect) {
                $this->db->table('inventory_qc_defects')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'inspection_id' => $inspectionId,
                    'defect_type' => $defect['type'],
                    'defect_description' => $defect['description'],
                    'severity' => $defect['severity'] ?? 'minor',
                    'quantity' => $defect['quantity'] ?? 1,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            if ($result === 'fail' || $result === 'conditional') {
                $this->quarantineBatch($inspection->batch_id, $result, $notes, $inspectorId, $tenantId);
            }

            $this->logAction(
                action: 'inspection_results_recorded',
                entityType: 'InventoryQCInspection',
                entityId: $inspectionId,
                context: [
                    'correlation_id' => $correlationId,
                    'result' => $result,
                    'defects_count' => count($defects),
                ],
                userId: $inspectorId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Quarantine batch
     *
     * @param  int|null  $batchId  Batch ID
     * @param  string  $reason  Quarantine reason
     * @param  string|null  $notes  Notes
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Quarantine ID
     */
    public function quarantineBatch(
        ?int $batchId,
        string $reason,
        ?string $notes,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $batchId,
            $reason,
            $notes,
            $userId,
            $tenantId,
            $correlationId
        ) {
            if ($batchId) {
                $this->db->table('inventory_batches')
                    ->where('id', $batchId)
                    ->update(['status' => 'quarantined']);
            }

            $quarantineId = $this->db->table('inventory_quarantines')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'quarantine_number' => $this->generateQuarantineNumber(),
                'batch_id' => $batchId,
                'reason' => $reason,
                'notes' => $notes,
                'status' => 'quarantined',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'batch_quarantined',
                entityType: 'InventoryQuarantine',
                entityId: $quarantineId,
                context: [
                    'correlation_id' => $correlationId,
                    'batch_id' => $batchId,
                    'reason' => $reason,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $quarantineId;
        });
    }

    /**
     * Release quarantine
     *
     * @param  int  $quarantineId  Quarantine ID
     * @param  string  $releaseReason  Release reason
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function releaseQuarantine(
        int $quarantineId,
        string $releaseReason,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $quarantineId,
            $releaseReason,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $quarantine = $this->db->table('inventory_quarantines')
                ->where('id', $quarantineId)
                ->where('status', 'quarantined')
                ->lockForUpdate()
                ->first();

            if (! $quarantine) {
                throw new \RuntimeException("Active quarantine {$quarantineId} not found");
            }

            if ($quarantine->batch_id) {
                $this->db->table('inventory_batches')
                    ->where('id', $quarantine->batch_id)
                    ->update(['status' => 'available']);
            }

            $this->db->table('inventory_quarantines')
                ->where('id', $quarantineId)
                ->update([
                    'status' => 'released',
                    'release_reason' => $releaseReason,
                    'released_by' => $userId,
                    'released_at' => now(),
                ]);

            $this->logAction(
                action: 'quarantine_released',
                entityType: 'InventoryQuarantine',
                entityId: $quarantineId,
                context: [
                    'correlation_id' => $correlationId,
                    'release_reason' => $releaseReason,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Get quality metrics
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $days  Number of days (default: 30)
     * @return array Quality metrics
     */
    public function getQualityMetrics(int $tenantId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->toDateString();

        $inspections = $this->db->table('inventory_qc_inspections')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalInspections = $inspections->count();
        $passedInspections = $inspections->where('result', 'pass')->count();
        $failedInspections = $inspections->where('result', 'fail')->count();
        $conditionalInspections = $inspections->where('result', 'conditional')->count();

        $passRate = $totalInspections > 0 ? ($passedInspections / $totalInspections) * 100 : 0;
        $failRate = $totalInspections > 0 ? ($failedInspections / $totalInspections) * 100 : 0;

        $totalDefects = $inspections->sum('defects_count');

        $quarantines = $this->db->table('inventory_quarantines')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $activeQuarantines = $quarantines->where('status', 'quarantined')->count();

        return [
            'tenant_id' => $tenantId,
            'period_days' => $days,
            'total_inspections' => $totalInspections,
            'passed' => $passedInspections,
            'failed' => $failedInspections,
            'conditional' => $conditionalInspections,
            'pass_rate' => round($passRate, 2),
            'fail_rate' => round($failRate, 2),
            'total_defects' => $totalDefects,
            'active_quarantines' => $activeQuarantines,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate inspection number
     *
     * @return string Inspection number
     */
    private function generateInspectionNumber(): string
    {
        $prefix = 'QC';
        $date = now()->format('Ymd');
        $sequence = $this->db->table('inventory_qc_inspections')
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Generate quarantine number
     *
     * @return string Quarantine number
     */
    private function generateQuarantineNumber(): string
    {
        $prefix = 'QTN';
        $date = now()->format('Ymd');
        $sequence = $this->db->table('inventory_quarantines')
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
