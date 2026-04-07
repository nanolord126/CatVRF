<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Services;

use App\Domains\GroceryAndDelivery\Models\DeliveryPartner;
use App\Domains\GroceryAndDelivery\Models\GroceryOrder;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Сервис быстрой доставки для вертикали GroceryAndDelivery.
 *
 * Функциональность:
 * - Поиск и назначение оптимального курьера (delivery partner) на заказ.
 * - Выбор партнёра по рейтингу и доступности.
 *
 * Правила назначения:
 * - Выбирается партнёр со статусом 'available' для данного магазина.
 * - Приоритет — по убыванию рейтинга.
 * - Если нет доступных партнёров — выбрасывается исключение.
 *
 * Все операции логируются с correlation_id через audit-канал.
 */
final readonly class FastDeliveryService
{
    /**
     * Найти оптимального партнёра доставки и назначить на заказ.
     *
     * @throws \RuntimeException Если нет доступных партнёров
     */
    public function assignDeliveryPartner(
        GroceryOrder $order,
        string $correlationId,
    ): DeliveryPartner {
        $partnerRow = $this->db->table('delivery_partners')
            ->where('store_id', $order->store_id)
            ->where('status', 'available')
            ->orderByRaw('rating DESC')
            ->first();

        if ($partnerRow === null) {
            throw new \RuntimeException(
                "No available delivery partners for store {$order->store_id}"
            );
        }

        $partner = DeliveryPartner::findOrFail($partnerRow->id);

        $order->update(['delivery_partner_id' => $partner->id]);

        $this->logger->channel('audit')->info('Delivery partner assigned via FastDeliveryService', [
            'order_id' => $order->id,
            'partner_id' => $partner->id,
            'partner_rating' => $partner->rating,
            'correlation_id' => $correlationId,
        ]);

        return $partner;
    }
}
