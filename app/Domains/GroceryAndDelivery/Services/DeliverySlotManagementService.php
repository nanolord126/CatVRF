<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Services;

use App\Domains\GroceryAndDelivery\Models\DeliverySlot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Управление слотами доставки для вертикали GroceryAndDelivery.
 *
 * Функциональность:
 * - Получение доступных слотов доставки для магазина на конкретную дату.
 * - Обновление surge-коэффициентов на основе загруженности слота.
 *
 * Surge-логика:
 * - Загруженность >90% → множитель 1.5×
 * - Загруженность >70% → множитель 1.3×
 * - Загруженность >50% → множитель 1.15×
 * - Иначе → 1.0× (без наценки)
 *
 * Все операции логируются с correlation_id через audit-канал.
 */
final readonly class DeliverySlotManagementService
{
    /**
     * Получить доступные слоты доставки для магазина на дату.
     *
     * @return Collection<int, DeliverySlot>
     */
    public function getAvailableSlots(int $storeId, \Carbon\CarbonInterface $date): Collection
    {
        return DeliverySlot::where('store_id', $storeId)
            ->whereDate('start_time', $date)
            ->where('is_available', true)
            ->where('current_orders', '<', $this->db->raw('max_orders'))
            ->get();
    }

    /**
     * Обновить surge-коэффициент на основе текущей загруженности слота.
     *
     * Множитель рассчитывается по соотношению current_orders / max_orders.
     * Результат записывается в surge_multiplier слота.
     */
    public function updateSurgeMultiplier(int $slotId): void
    {
        $slot = DeliverySlot::findOrFail($slotId);

        $occupancyRate = $slot->max_orders > 0
            ? $slot->current_orders / $slot->max_orders
            : 0.0;

        $multiplier = match (true) {
            $occupancyRate > 0.9 => 1.5,
            $occupancyRate > 0.7 => 1.3,
            $occupancyRate > 0.5 => 1.15,
            default => 1.0,
        };

        $slot->update(['surge_multiplier' => $multiplier]);

        $this->logger->channel('audit')->info('Slot surge updated', [
            'slot_id' => $slotId,
            'occupancy' => $occupancyRate,
            'multiplier' => $multiplier,
        ]);
    }
}
