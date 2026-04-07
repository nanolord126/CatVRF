<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Services\FraudControlService;
use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautyConsumable;
use Illuminate\Support\Str;

final readonly class ConsumableDeductionService
{
    public function __construct(
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private FraudControlService $fraud,
    ) {}



    /**
     * Зарезервировать расходники при записи.
     */
    public function reserveForAppointment(Appointment $appointment): void
    {
        $service = $appointment->service;
        if (!$service || empty($service->consumables)) {
            return;
        }

        $correlationId = $appointment->correlation_id ?? Str::uuid()->toString();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'consumable_reserve',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($appointment, $service, $correlationId): void {
            foreach ($service->consumables as $item) {
                $consumable = BeautyConsumable::where('salon_id', $appointment->salon_id)
                    ->where('name', $item['name'])
                    ->lockForUpdate()
                    ->first();

                if (!$consumable) {
                    continue;
                }

                $qty = $item['quantity'] ?? 1;

                if ($consumable->current_stock < $qty) {
                    $this->logger->warning('Insufficient stock for reservation', [
                        'consumable'     => $item['name'],
                        'salon_id'       => $appointment->salon_id,
                        'appointment_id' => $appointment->id,
                        'required'       => $qty,
                        'available'      => $consumable->current_stock,
                        'correlation_id' => $correlationId,
                    ]);
                    continue;
                }

                $consumable->increment('reserved_stock', $qty);

                $this->logger->info('Consumable reserved', [
                    'consumable_id'  => $consumable->id,
                    'qty'            => $qty,
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $correlationId,
                ]);
            }
        });
    }

    /**
     * Снять бронь с расходников при отмене записи.
     *
     * Возвращает зарезервированное количество обратно в доступный пул.
     */
    public function releaseForAppointment(Appointment $appointment): void
    {
        $service = $appointment->service;
        if (!$service || empty($service->consumables)) {
            return;
        }

        $correlationId = $appointment->correlation_id ?? Str::uuid()->toString();

        $this->db->transaction(function () use ($appointment, $service, $correlationId): void {
            foreach ($service->consumables as $item) {
                $consumable = BeautyConsumable::where('salon_id', $appointment->salon_id)
                    ->where('name', $item['name'])
                    ->lockForUpdate()
                    ->first();

                if (!$consumable) {
                    continue;
                }

                $qty = $item['quantity'] ?? 1;
                $releaseQty = min($qty, $consumable->reserved_stock);

                if ($releaseQty > 0) {
                    $consumable->decrement('reserved_stock', $releaseQty);
                }

                $this->logger->info('Consumable reservation released', [
                    'consumable_id'  => $consumable->id,
                    'qty'            => $releaseQty,
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $correlationId,
                ]);
            }
        });
    }

    /**
     * Реальное списание расходников после выполнения услуги.
     */
    public function deductForAppointment(Appointment $appointment): void
    {
        $service = $appointment->service;
        if (!$service || empty($service->consumables)) {
            return;
        }

        $correlationId = $appointment->correlation_id ?? Str::uuid()->toString();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'consumable_deduction',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($appointment, $service, $correlationId): void {
            foreach ($service->consumables as $item) {
                $consumable = BeautyConsumable::where('salon_id', $appointment->salon_id)
                    ->where('name', $item['name'])
                    ->lockForUpdate()
                    ->first();

                if (!$consumable) {
                    continue;
                }

                $qty = $item['quantity'] ?? 1;

                $consumable->decrement('current_stock', $qty);

                $releaseQty = min($qty, $consumable->reserved_stock);
                if ($releaseQty > 0) {
                    $consumable->decrement('reserved_stock', $releaseQty);
                }

                $this->logger->info('Consumable deducted', [
                    'consumable_id'  => $consumable->id,
                    'qty'            => $qty,
                    'appointment_id' => $appointment->id,
                    'reason'         => 'Service completion: ' . $service->name,
                    'correlation_id' => $correlationId,
                ]);

                if ($consumable->current_stock <= ($consumable->min_threshold ?? 5)) {
                    $this->logger->warning('Emergency low stock alert', [
                        'consumable_id'  => $consumable->id,
                        'current_stock'  => $consumable->current_stock,
                        'min_threshold'  => $consumable->min_threshold ?? 5,
                        'correlation_id' => $correlationId,
                    ]);
                }
            }
        });
    }
}
