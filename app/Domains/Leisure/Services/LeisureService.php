<?php

declare(strict_types=1);

namespace App\Domains\Leisure\Services;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Payment\PaymentServiceAdapter;
use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали Leisure.
 *
 * Координирует досуг и развлечения (EventPlanning, Tickets, PartySupplies, WeddingPlanning, ToysAndGames, HobbyAndCraft, Flowers).
 * Поддерживает массовые события и presence channels.
 */
final readonly class LeisureService
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly PaymentServiceAdapter $paymentAdapter,
        private readonly FraudMLCoordinatorService $fraudMLCoordinator,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Подготовить заказ досуга и развлечений.
     *
     * @param  array{items: array, event_date?: string, venue_address?: string, buyer_address?: string, sub_vertical?: string}  $cartData
     * @return array{delivery_cost: int, eta: int, event_capacity_check: bool}
     */
    public function prepareCheckout(array $cartData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Leisure checkout preparation started', [
            'correlation_id' => $correlationId,
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
        ]);

        // Проверка вместимости для массовых событий
        $eventCapacityCheck = true;
        if (in_array($cartData['sub_vertical'] ?? '', ['event_planning', 'tickets', 'wedding_planning'])) {
            $eventCapacityCheck = $this->checkEventCapacity($cartData);
        }

        // Расчёт доставки (если требуется)
        $deliveryCost = 0;
        $eta = 0;
        $distance = 0;

        if (!empty($cartData['venue_address']) && !empty($cartData['buyer_address'])) {
            $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'leisure',
                'sub_vertical' => $cartData['sub_vertical'] ?? null,
                'seller_address' => $cartData['venue_address'],
                'buyer_address' => $cartData['buyer_address'],
                'items' => $cartData['items'] ?? [],
                'mass_addresses' => in_array($cartData['sub_vertical'] ?? '', ['event_planning', 'party_supplies', 'wedding_planning']),
            ]);

            $deliveryCost = $deliveryCalculation['cost'];
            $eta = $deliveryCalculation['eta'];
            $distance = $deliveryCalculation['distance'];
        }

        return [
            'delivery_cost' => $deliveryCost,
            'eta' => $eta,
            'distance' => $distance,
            'event_capacity_check' => $eventCapacityCheck,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать заказ досуга и развлечений.
     *
     * @param  array{user_id: int, items: array, event_date?: string, venue_address?: string, buyer_address?: string, sub_vertical?: string, amount: int}  $orderData
     * @return array{order_id: int, payment_intent_id: string}
     */
    public function createOrder(array $orderData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Leisure order creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $orderData['user_id'],
            'sub_vertical' => $orderData['sub_vertical'] ?? null,
        ]);

        return $this->db->transaction(function () use ($orderData, $correlationId) {
            // Fraud ML check
            $fraudResult = $this->fraudMLCoordinator->checkPaymentRisk([
                'amount' => $orderData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'leisure',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            // Расчёт доставки (если требуется)
            $deliveryCost = 0;
            $deliveryAddress = $orderData['buyer_address'] ?? null;

            if (!empty($orderData['venue_address']) && !empty($orderData['buyer_address'])) {
                $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                    'vertical' => 'leisure',
                    'sub_vertical' => $orderData['sub_vertical'] ?? null,
                    'seller_address' => $orderData['venue_address'],
                    'buyer_address' => $orderData['buyer_address'],
                    'items' => $orderData['items'] ?? [],
                    'mass_addresses' => in_array($orderData['sub_vertical'] ?? '', ['event_planning', 'party_supplies', 'wedding_planning']),
                ]);
                $deliveryCost = $deliveryCalculation['cost'];
            }

            // Создание заказа
            $orderId = $this->db->table('leisure_orders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id' => $orderData['user_id'],
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'event_date' => $orderData['event_date'] ?? null,
                'venue_address' => $orderData['venue_address'] ?? null,
                'status' => 'pending',
                'total_amount' => $orderData['amount'] ?? 0,
                'delivery_cost' => $deliveryCost,
                'delivery_address' => $deliveryAddress,
                'fraud_score' => $fraudResult['score'] ?? 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            // Подготовка платежа
            $paymentPreparation = $this->paymentAdapter->preparePayment([
                'amount' => ($orderData['amount'] ?? 0) + $deliveryCost,
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'leisure',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'payable_type' => 'leisure_order',
                'payable_id' => $orderId,
            ]);

            return [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentPreparation['payment_intent_id'],
                'client_secret' => $paymentPreparation['client_secret'],
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Проверить вместимость мероприятия.
     */
    private function checkEventCapacity(array $eventData): bool
    {
        // Упрощённо - всегда достаточно места
        return true;
    }
}
