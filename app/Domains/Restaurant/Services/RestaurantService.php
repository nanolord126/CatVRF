<?php

declare(strict_types=1);

namespace App\Domains\Restaurant\Services;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Realtime\RealtimeTrackingAdapter;
use App\Domains\Shared\Payment\PaymentServiceAdapter;
use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали Restaurant.
 *
 * Координирует под-вертикали (Food, Catering) и предоставляет
 * унифицированный интерфейс для работы с заказами еды.
 */
final readonly class RestaurantService
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly RealtimeTrackingAdapter $trackingAdapter,
        private readonly PaymentServiceAdapter $paymentAdapter,
        private readonly FraudMLCoordinatorService $fraudMLCoordinator,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Подготовить чекаут: рассчитать доставку и доступные слоты.
     *
     * @param  array{items: array, restaurant_address: string, customer_address: string, sub_vertical: string}  $cartData
     * @return array{delivery_cost: int, eta: int, available_slots: array}
     */
    public function prepareCheckout(array $cartData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Restaurant checkout preparation started', [
            'correlation_id' => $correlationId,
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
        ]);

        // Расчёт доставки через GeoLogistics (быстрая доставка для ресторанов)
        $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
            'vertical' => 'restaurant',
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
            'seller_address' => $cartData['restaurant_address'],
            'buyer_address' => $cartData['customer_address'],
            'items' => $cartData['items'] ?? [],
            'priority' => 'high', // Рестораны - быстрая доставка
        ]);

        // Получить доступные слоты доставки (короткие окна для ресторанов)
        $availableSlots = $this->geoAdapter->getAvailableSlots(
            address: $cartData['customer_address'],
            vertical: 'restaurant',
            subVertical: $cartData['sub_vertical'] ?? null,
        );

        return [
            'delivery_cost' => $deliveryCalculation['cost'],
            'eta' => $deliveryCalculation['eta'],
            'distance' => $deliveryCalculation['distance'],
            'available_slots' => $availableSlots,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать заказ с учётом доставки и оплаты.
     *
     * @param  array{user_id: int, items: array, restaurant_address: string, customer_address: string, delivery_slot: string, sub_vertical: string, amount: int}  $orderData
     * @return array{order_id: int, payment_intent_id: string, delivery_cost: int, eta: int}
     */
    public function createOrder(array $orderData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Restaurant order creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $orderData['user_id'],
            'sub_vertical' => $orderData['sub_vertical'] ?? null,
        ]);

        return $this->db->transaction(function () use ($orderData, $correlationId) {
            // Fraud ML check before payment
            $fraudResult = $this->fraudMLCoordinator->checkPaymentRisk([
                'amount' => $orderData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'restaurant',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            // Расчёт доставки
            $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'restaurant',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'seller_address' => $orderData['restaurant_address'],
                'buyer_address' => $orderData['customer_address'],
                'items' => $orderData['items'] ?? [],
                'priority' => 'high',
            ]);

            // Создание заказа
            $orderId = $this->db->table('restaurant_orders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id' => $orderData['user_id'],
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'status' => 'pending',
                'total_amount' => $orderData['amount'] ?? 0,
                'delivery_cost' => $deliveryCalculation['cost'],
                'delivery_eta' => $deliveryCalculation['eta'],
                'delivery_address' => $orderData['customer_address'],
                'delivery_slot' => $orderData['delivery_slot'],
                'fraud_score' => $fraudResult['score'] ?? 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->logger->info('Restaurant order created', [
                'order_id' => $orderId,
                'correlation_id' => $correlationId,
                'delivery_cost' => $deliveryCalculation['cost'],
            ]);

            // Подготовка платежа через PaymentServiceAdapter
            $paymentPreparation = $this->paymentAdapter->preparePayment([
                'amount' => $orderData['amount'] ?? 0 + $deliveryCalculation['cost'],
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'restaurant',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'payable_type' => 'restaurant_order',
                'payable_id' => $orderId,
            ]);

            // Запуск реалтайм-трекинга заказа
            $trackingSession = $this->trackingAdapter->startTracking([
                'order_id' => $orderId,
                'vertical' => 'restaurant',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'courier_id' => null,
                'buyer_id' => $orderData['user_id'],
            ], $correlationId);

            return [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentPreparation['payment_intent_id'],
                'client_secret' => $paymentPreparation['client_secret'],
                'delivery_cost' => $deliveryCalculation['cost'],
                'eta' => $deliveryCalculation['eta'],
                'distance' => $deliveryCalculation['distance'],
                'tracking_session' => $trackingSession,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Проверить доступность доставки для адреса.
     */
    public function checkDeliveryAvailability(string $address): bool
    {
        return $this->geoAdapter->isAddressInDeliveryZone($address, 'restaurant');
    }
}
