<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\Appointment;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

/**
 * Beauty Salon Management Service
 * CANON 2026 - Production Ready
 */
final class BeautySalonService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createSalon(array $data, int $tenantId, string $correlationId): BeautySalon
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId
        );

        return $this->db->transaction(function () use ($data, $tenantId, $correlationId) {
            $this->log->channel('audit')->info('Creating beauty salon', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'salon_name' => $data['name'],
            ]);

            $salon = BeautySalon::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'address' => $data['address'],
                'geo_point' => $data['geo_point'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'],
                'schedule_json' => json_encode($data['schedule'] ?? []),
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);

            return $salon;
        });
    }

    public function updateSalon(BeautySalon $salon, array $data, string $correlationId): BeautySalon
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId
        );

        return $this->db->transaction(function () use ($salon, $data, $correlationId) {
            $this->log->channel('audit')->info('Updating beauty salon', [
                'correlation_id' => $correlationId,
                'salon_id' => $salon->id,
            ]);

            $salon->update($data + ['correlation_id' => $correlationId]);
            return $salon;
        });
    }

    public function getSalonStats(BeautySalon $salon): array
    {
        $totalAppointments = Appointment::query()
            ->where('salon_id', $salon->id)
            ->where('status', 'completed')
            ->count();

        $totalRevenue = Appointment::query()
            ->where('salon_id', $salon->id)
            ->where('status', 'completed')
            ->where('paid_at', '!=', null)
            ->sum('price');

        $activeMasters = Master::query()
            ->where('salon_id', $salon->id)
            ->where('is_active', true)
            ->count();

        return [
            'total_appointments' => $totalAppointments,
            'total_revenue' => $totalRevenue,
            'active_masters' => $activeMasters,
            'rating' => $salon->rating ?? 0,
        ];
    }

    public function deactivateSalon(BeautySalon $salon, string $correlationId): bool
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId
        );

        return $this->db->transaction(function () use ($salon, $correlationId) {
            $this->log->channel('audit')->info('Deactivating beauty salon', [
                'correlation_id' => $correlationId,
                'salon_id' => $salon->id,
            ]);

            $salon->update([
                'is_active' => false,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }
}
