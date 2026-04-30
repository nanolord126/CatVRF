<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Application\DTOs\OrderItemDTO;
use Modules\Restaurant\Domain\Enums\OrderPriority;
use Modules\Restaurant\Domain\Events\OrderSentToKitchen;
use Modules\Restaurant\Domain\ValueObjects\PreparationTime;
use App\Models\Order;
use App\Models\OrderItem;

final readonly class MarketplaceOrderIntegrationService
{
    public function __construct(
        private KitchenService $kitchenService,
    ) {}

    /**
     * Обработка заказа из маркетплейса CatVRF
     * Автоматически маршрутизирует заказы на соответствующие кухонные станции
     */
    public function processMarketplaceOrder(Order $order): Collection
    {
        if ($order->vertical !== 'restaurant' && $order->vertical !== 'food') {
            return collect();
        }

        Log::info('Processing marketplace order for KDS', [
            'order_id' => $order->id,
            'vertical' => $order->vertical,
        ]);

        // Определяем VIP статус
        $isVip = $this->isVipOrder($order);

        // Конвертируем OrderItem в DTO
        $orderItems = $order->items->map(fn (OrderItem $item) => new OrderItemDTO(
            id: $item->id,
            name: $item->product_name,
            category: $this->extractCategory($item),
            quantity: $item->quantity,
            preparationMinutes: $this->getPreparationTime($item),
            isUrgent: $this->isUrgentOrder($order),
            modifiers: $item->options ?? [],
        ));

        // Маршрутизируем заказ на кухню
        $kitchenStatuses = $this->kitchenService->routeOrderToStation(
            orderId: $order->id,
            orderItems: $orderItems,
            tenantId: $order->tenant_id,
            isFromMarketplace: true,
            isVip: $isVip,
        );

        // Транслируем события
        foreach ($kitchenStatuses as $status) {
            event(new OrderSentToKitchen($status));
        }

        Log::info('Marketplace order routed to kitchen', [
            'order_id' => $order->id,
            'kitchen_statuses_count' => $kitchenStatuses->count(),
        ]);

        return $kitchenStatuses;
    }

    /**
     * Определение категории блюда для маршрутизации на станцию
     */
    private function extractCategory(OrderItem $item): ?string
    {
        $options = $item->options ?? [];
        
        // Пытаемся определить категорию из options или product_name
        if (isset($options['category'])) {
            return $options['category'];
        }

        $name = strtolower($item->product_name ?? '');
        
        return match (true) {
            str_contains($name, 'салат') || str_contains($name, 'salad') => 'salad',
            str_contains($name, 'напиток') || str_contains($name, 'drink') || str_contains($name, 'coke') => 'drink',
            str_contains($name, 'коктейль') || str_contains($name, 'cocktail') => 'cocktail',
            str_contains($name, 'пицца') || str_contains($name, 'pizza') => 'pizza',
            str_contains($name, 'суши') || str_contains($name, 'sushi') || str_contains($name, 'ролл') => 'sushi',
            str_contains($name, 'десерт') || str_contains($name, 'cake') || str_contains($name, 'торт') => 'dessert',
            str_contains($name, 'гриль') || str_contains($name, 'grill') || str_contains($name, 'стейк') => 'grill',
            default => 'hot',
        };
    }

    /**
     * Получение времени приготовления из настроек или дефолтное
     */
    private function getPreparationTime(OrderItem $item): int
    {
        $options = $item->options ?? [];
        
        if (isset($options['preparation_minutes'])) {
            return (int) $options['preparation_minutes'];
        }

        // Дефолтные времена по категориям
        $category = $this->extractCategory($item);
        
        return match ($category) {
            'drink', 'cocktail' => 5,
            'salad' => 10,
            'dessert' => 15,
            'pizza' => 20,
            'sushi' => 25,
            'grill' => 30,
            default => 15,
        };
    }

    /**
     * Проверка VIP статуса заказа
     */
    private function isVipOrder(Order $order): bool
    {
        // Проверяем теги заказа
        $tags = $order->tags ?? [];
        
        if (in_array('vip', $tags, true)) {
            return true;
        }

        // Проверяем уровень лояльности пользователя
        if ($order->user && $order->user->loyalty_level === 'platinum') {
            return true;
        }

        return false;
    }

    /**
     * Проверка срочности заказа
     */
    private function isUrgentOrder(Order $order): bool
    {
        $tags = $order->tags ?? [];
        
        return in_array('urgent', $tags, true) || in_array('express', $tags, true);
    }

    /**
     * Определение приоритета заказа из маркетплейса
     */
    public function determineMarketplaceOrderPriority(Order $order): OrderPriority
    {
        if ($this->isVipOrder($order)) {
            return OrderPriority::VIP;
        }

        if ($this->isUrgentOrder($order)) {
            return OrderPriority::URGENT;
        }

        // Заказы из маркетплейса имеют повышенный приоритет по умолчанию
        return OrderPriority::HIGH;
    }

    /**
     * Обновление статуса заказа на кухне при изменении статуса в маркетплейсе
     */
    public function syncOrderStatus(Order $order): void
    {
        if ($order->vertical !== 'restaurant' && $order->vertical !== 'food') {
            return;
        }

        // Если заказ отменён в маркетплейсе, отменяем и на кухне
        if ($order->status === Order::STATUS_CANCELLED) {
            $kitchenStatuses = $this->kitchenService->orderKitchenStatusRepository
                ->findByOrderId($order->id);

            foreach ($kitchenStatuses as $status) {
                if ($status->status->value !== 'served' && $status->status->value !== 'cancelled') {
                    $this->kitchenService->cancelOrder($status->id);
                }
            }
        }
    }
}
