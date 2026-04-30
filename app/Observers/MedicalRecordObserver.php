<?php

declare(strict_types=1);

namespace App\Observers;

use Psr\Log\LoggerInterface;

use App\Domains\Medical\Models\MedicalRecord;
use App\Services\Cache\CacheService;
use Illuminate\Log\LogManager;

/**
 * Medical Record Observer
 *
 * Production 2026 CANON - Automatic Cache Invalidation
 *
 * Automatically invalidates cache when medical records are created, updated, or deleted.
 * This ensures that diagnosis and health score predictions are always based on fresh data.
 *
 * @author CatVRF Team
 *
 * @version 2026.04.18
 */
final readonly class MedicalRecordObserver
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly CacheService $cache,
        private readonly LogManager $log,) {}

    /**
     * Handle the MedicalRecord "created" event.
     */
    public function created(MedicalRecord $record): void
    {
        $this->invalidateRelatedCache($record);
    }

    /**
     * Handle the MedicalRecord "updated" event.
     */
    public function updated(MedicalRecord $record): void
    {
        $this->invalidateRelatedCache($record);
    }

    /**
     * Handle the MedicalRecord "deleted" event.
     */
    public function deleted(MedicalRecord $record): void
    {
        $this->invalidateRelatedCache($record);
    }

    /**
     * Invalidate all related cache for the medical record.
     */
    private function invalidateRelatedCache(MedicalRecord $record): void
    {
        $tenantId = $record->tenant_id;
        $patientId = $record->patient_id;

        try {
            // Invalidate diagnosis cache for the patient
            $this->cache->invalidateDiagnostic($tenantId, $patientId);

            // Invalidate health score cache for the patient
            $this->cache->invalidateHealthScore($tenantId, $patientId);

            // Invalidate recommendations cache for the patient
            $this->cache->invalidateRecommendations($tenantId, $patientId);

            // Invalidate doctor cache (if doctor is associated)
            if ($record->doctor_id) {
                $this->cache->invalidateDoctor($tenantId, $record->doctor_id);
            }

            $this->log->$this->logger->info('Medical record cache invalidated', [
                'tenant_id' => $tenantId,
                'patient_id' => $patientId,
                'record_id' => $record->id,
                'doctor_id' => $record->doctor_id,
            ]);
        } catch (\Exception $e) {
            $this->log->error('Failed to invalidate medical record cache', [
                'tenant_id' => $tenantId,
                'patient_id' => $patientId,
                'record_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
