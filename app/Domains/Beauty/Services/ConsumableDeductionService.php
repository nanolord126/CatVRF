<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautyConsumable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026: Consumable Deduction Service (Layer 3)
 * Автоматическое управление остатками расходников.
 * 
 * Особенности:
 * - Использование lockForUpdate() при списании.
 * - Логирование в канал inventory.
 * - Интеграция с сервисом уведомлений (NotificationService - по желанию).
 */
final readonly class ConsumableDeductionService
{
    /**
     * Зарезервировать расходники при записи.
     */
    public function reserveForAppointment(Appointment $appointment): void
    {
        $service = $appointment->service;
        if (!$service || empty($service->consumables)) {
            return;
        }

        foreach ($service->consumables as $item) {
            $consumable = BeautyConsumable::where('salon_id', $appointment->salon_id)
                ->where('name', $item['name'])
                ->first();

            if ($consumable) {
                if ($consumable->current_stock < ($item['quantity'] ?? 1)) {
                    Log::channel('inventory')->warning('Insufficient stock for reservation', [
                        'consumable' => $item['name'],
                        'salon_id' => $appointment->salon_id,
                        'appointment_id' => $appointment->id
                    ]);
                }
            }
        }
    }

    /**
     * Снять бронь с расходников.
     */
    public function releaseForAppointment(Appointment $appointment): void
    {
        Log::channel('inventory')->info('Release consumables for cancelled appointment', [
            'appointment_id' => $appointment->id
        ]);
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

        DB::transaction(function () use ($appointment, $service) {
            foreach ($service->consumables as $item) {
                $consumable = BeautyConsumable::where('salon_id', $appointment->salon_id)
                    ->where('name', $item['name'])
                    ->lockForUpdate()
                    ->first();

                if ($consumable) {
                    $qty = $item['quantity'] ?? 1;
                    $consumable->decrement('current_stock', $qty);

                    Log::channel('inventory')->info('Consumable deducted', [
                        'consumable_id' => $consumable->id,
                        'qty' => $qty,
                        'appointment_id' => $appointment->id,
                        'reason' => 'Service completion: ' . $service->name
                    ]);
                    
                    if ($consumable->current_stock <= $consumable->min_threshold) {
                        Log::channel('inventory')->warning('Emergency low stock alert', [
                            'consumable_id' => $consumable->id,
                            'current_stock' => $consumable->current_stock
                        ]);
                    }
                }
            }
        });
    }
}
