<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Models\Master;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * BeautySalonService — CRUD и управление салонами красоты.
 *
 * CANON 2026: FraudControlService::check() + DB::transaction() + correlation_id + AuditService.
 * Никаких фасадов, только constructor injection.
 */
final readonly class BeautySalonService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Создать новый салон красоты.
     */
    public function createSalon(array $data, int $tenantId, string $correlationId): BeautySalon
    {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_create_salon',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $tenantId, $correlationId): BeautySalon {
            $salon = BeautySalon::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid()->toString(),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'address' => $data['address'],
                'geo_point' => $data['geo_point'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'],
                'schedule_json' => json_encode($data['schedule'] ?? [], JSON_THROW_ON_ERROR),
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record(
                action: 'beauty_salon_created',
                subjectType: BeautySalon::class,
                subjectId: $salon->id,
                oldValues: [],
                newValues: $salon->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty salon created', [
                'salon_id' => $salon->id,
                'tenant_id' => $tenantId,
                'salon_name' => $data['name'],
                'correlation_id' => $correlationId,
            ]);

            return $salon;
        });
    }

    /**
     * Обновить данные салона.
     */
    public function updateSalon(BeautySalon $salon, array $data, string $correlationId): BeautySalon
    {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_update_salon',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($salon, $data, $correlationId): BeautySalon {
            $oldValues = $salon->toArray();

            $salon->update(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            $this->audit->record(
                action: 'beauty_salon_updated',
                subjectType: BeautySalon::class,
                subjectId: $salon->id,
                oldValues: $oldValues,
                newValues: $salon->fresh()->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty salon updated', [
                'salon_id' => $salon->id,
                'changed_fields' => array_keys($data),
                'correlation_id' => $correlationId,
            ]);

            return $salon;
        });
    }

    /**
     * Деактивировать салон (soft-деактивация, не удаление).
     */
    public function deactivateSalon(BeautySalon $salon, string $correlationId): bool
    {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_deactivate_salon',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($salon, $correlationId): bool {
            $salon->update([
                'is_active' => false,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record(
                action: 'beauty_salon_deactivated',
                subjectType: BeautySalon::class,
                subjectId: $salon->id,
                oldValues: ['is_active' => true],
                newValues: ['is_active' => false],
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty salon deactivated', [
                'salon_id' => $salon->id,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }

    /**
     * Получить статистику по салону (выполненные записи, выручка, активные мастера).
     */
    public function getSalonStats(BeautySalon $salon): array
    {
        $completedQuery = Appointment::query()
            ->where('salon_id', $salon->id)
            ->where('status', 'completed');

        $totalAppointments = $completedQuery->count();

        $totalRevenue = (clone $completedQuery)
            ->whereNotNull('paid_at')
            ->sum('price');

        $activeMasters = Master::query()
            ->where('salon_id', $salon->id)
            ->where('is_active', true)
            ->count();

        return [
            'total_appointments' => $totalAppointments,
            'total_revenue' => (float) $totalRevenue,
            'active_masters' => $activeMasters,
            'rating' => (float) ($salon->rating ?? 0),
        ];
    }

    /**
     * Получить салон по ID (с tenant-scoping из global scope).
     */
    public function findById(int $salonId): BeautySalon
    {
        $salon = BeautySalon::find($salonId);

        if ($salon === null) {
            throw new \DomainException("Салон (id={$salonId}) не найден для текущего тенанта.");
        }

        return $salon;
    }
}
