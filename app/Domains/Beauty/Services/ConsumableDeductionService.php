<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use Illuminate\Support\Str;


use App\Domains\Beauty\Models\BeautyConsumable;
use Illuminate\Support\Facades\DB;

final class ConsumableDeductionService
{
    public function __construct()
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Beauty', ['correlation_id' => $correlationId]);
        FraudControlService::check('service_operation', ['correlation_id' => $correlationId]);

    }

    /**
     * Списать расходники после завершения услуги
     */
    public function deductConsumables(int $appointmentId, array $consumables, string $correlationId): bool
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Beauty', ['correlation_id' => $correlationId]);
        FraudControlService::check('service_operation', ['correlation_id' => $correlationId]);

        try {
            DB::transaction(function () use ($appointmentId, $consumables, $correlationId) {
                foreach ($consumables as $consumableId => $quantity) {
                    $consumable = BeautyConsumable::lockForUpdate()->findOrFail($consumableId);

                    if ($consumable->current_stock < $quantity) {
                        throw new \Exception("Insufficient consumable stock for {$consumableId}");
                    }

                    $consumable->decrement('current_stock', $quantity);

                    Log::channel('audit')->info('Consumable deducted', [
                        'appointment_id' => $appointmentId,
                        'consumable_id' => $consumableId,
                        'quantity' => $quantity,
                        'remaining' => $consumable->current_stock,
                        'correlation_id' => $correlationId,
                    ]);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Consumable deduction failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Зарезервировать расходники при бронировании
     */
    public function reserveConsumables(int $appointmentId, array $consumables, string $correlationId): bool
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Beauty', ['correlation_id' => $correlationId]);
        FraudControlService::check('service_operation', ['correlation_id' => $correlationId]);

        try {
            DB::transaction(function () use ($appointmentId, $consumables, $correlationId) {
                foreach ($consumables as $consumableId => $quantity) {
                    $consumable = BeautyConsumable::lockForUpdate()->findOrFail($consumableId);

                    if ($consumable->current_stock < $quantity) {
                        throw new \Exception("Insufficient consumable stock for {$consumableId}");
                    }

                    $consumable->increment('hold_stock', $quantity);

                    Log::channel('audit')->info('Consumable reserved', [
                        'appointment_id' => $appointmentId,
                        'consumable_id' => $consumableId,
                        'quantity' => $quantity,
                        'correlation_id' => $correlationId,
                    ]);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Consumable reservation failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Отпустить зарезервированные расходники при отмене
     */
    public function releaseConsumables(int $appointmentId, array $consumables, string $correlationId): bool
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Beauty', ['correlation_id' => $correlationId]);
        FraudControlService::check('service_operation', ['correlation_id' => $correlationId]);

        try {
            DB::transaction(function () use ($appointmentId, $consumables, $correlationId) {
                foreach ($consumables as $consumableId => $quantity) {
                    $consumable = BeautyConsumable::lockForUpdate()->findOrFail($consumableId);
                    $consumable->decrement('hold_stock', $quantity);

                    Log::channel('audit')->info('Consumable released', [
                        'appointment_id' => $appointmentId,
                        'consumable_id' => $consumableId,
                        'quantity' => $quantity,
                        'correlation_id' => $correlationId,
                    ]);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Consumable release failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
