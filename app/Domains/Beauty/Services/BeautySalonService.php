<?php declare(strict_types=1);

namespace Modules\Beauty\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Beauty\Models\BeautySalon;
use Modules\Beauty\Models\Master;
use Modules\Beauty\Models\Appointment;
use Modules\Inventory\Services\InventoryManagementService;
use Illuminate\Support\Str;

/**
 * Beauty Salon Management Service
 * CANON 2026 - Production Ready
 */
final class BeautySalonService
{
    public function __construct(
        private readonly InventoryManagementService $inventoryService,
    ) {}

    public function createSalon(array $data, int $tenantId, string $correlationId): BeautySalon
    {
        return DB::transaction(function () use ($data, $tenantId, $correlationId) {
            Log::channel('audit')->info('Creating beauty salon', [
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
        return DB::transaction(function () use ($salon, $data, $correlationId) {
            Log::channel('audit')->info('Updating beauty salon', [
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
        return DB::transaction(function () use ($salon, $correlationId) {
            Log::channel('audit')->info('Deactivating beauty salon', [
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
