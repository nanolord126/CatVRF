<?php declare(strict_types=1);

namespace App\Observers;

use App\Domains\Medical\Models\Appointment;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Log;

/**
 * Appointment Observer
 *
 * Production 2026 CANON - Automatic Cache Invalidation
 *
 * Automatically invalidates cache when appointments are created, updated, or deleted.
 * This ensures that slots and recommendations are always based on fresh data.
 *
 * @author CatVRF Team
 * @version 2026.04.18
 */
final readonly class AppointmentObserver
{
    public function __construct(
        private readonly CacheService $cache,
    ) {}

    /**
     * Handle the Appointment "created" event.
     */
    public function created(Appointment $appointment): void
    {
        $this->invalidateRelatedCache($appointment);
    }

    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Appointment $appointment): void
    {
        $this->invalidateRelatedCache($appointment);
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Appointment $appointment): void
    {
        $this->invalidateRelatedCache($appointment);
    }

    /**
     * Invalidate all related cache for the appointment.
     */
    private function invalidateRelatedCache(Appointment $appointment): void
    {
        $tenantId = $appointment->tenant_id;
        $doctorId = $appointment->doctor_id;
        $clinicId = $appointment->clinic_id;
        $clientId = $appointment->client_id;

        try {
            // Invalidate slots cache for the doctor
            if ($doctorId) {
                $this->cache->invalidateSlots($tenantId, $doctorId);
            }

            // Invalidate slots cache for the clinic
            if ($clinicId) {
                $this->cache->invalidateSlots($tenantId, null, $clinicId);
            }

            // Invalidate doctor cache
            if ($doctorId) {
                $this->cache->invalidateDoctor($tenantId, $doctorId);
            }

            // Invalidate clinic cache
            if ($clinicId) {
                $this->cache->invalidateClinic($tenantId, $clinicId);
            }

            // Invalidate recommendations cache for the client
            if ($clientId) {
                $this->cache->invalidateRecommendations($tenantId, $clientId);
            }

            // Invalidate dynamic price cache for the appointment
            $this->cache->invalidateDynamicPrice($tenantId, 'appointment', $appointment->id);

            Log::info('Appointment cache invalidated', [
                'tenant_id' => $tenantId,
                'appointment_id' => $appointment->id,
                'doctor_id' => $doctorId,
                'clinic_id' => $clinicId,
                'client_id' => $clientId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate appointment cache', [
                'tenant_id' => $tenantId,
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
