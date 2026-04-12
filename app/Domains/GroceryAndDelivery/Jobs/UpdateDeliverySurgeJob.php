<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Jobs;


use App\Domains\GroceryAndDelivery\Models\DeliverySlot;
use App\Domains\GroceryAndDelivery\Services\DeliverySlotManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Throwable;

/**
 * Обновляет surge-коэффициенты для слотов доставки конкретного магазина.
 *
 * Поток:
 * 1. Загружает все будущие слоты магазина.
 * 2. Для каждого слота вызывает DeliverySlotManagementService::updateSurgeMultiplier().
 * 3. Логирует количество обновлённых слотов с correlation_id.
 *
 * Запускается по расписанию или при изменении загруженности магазина.
 */
final class UpdateDeliverySurgeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $storeId,
        public readonly string $correlationId,
    ) {
        $this->onQueue('grocery-surge');
    }

    public function handle(DeliverySlotManagementService $slotService): void
    {
        try {
            $slots = DeliverySlot::where('store_id', $this->storeId)
                ->where('start_time', '>', now())
                ->get();

            foreach ($slots as $slot) {
                $slotService->updateSurgeMultiplier($slot->id);
            }

            app(\Psr\Log\LoggerInterface::class)->channel('audit')->info('Surge coefficients updated', [
                'store_id' => $this->storeId,
                'slots_updated' => $slots->count(),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            app(\Psr\Log\LoggerInterface::class)->channel('audit')->error('UpdateDeliverySurgeJob failed', [
                'store_id' => $this->storeId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}
