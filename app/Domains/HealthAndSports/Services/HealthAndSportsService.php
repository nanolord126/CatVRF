<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\Services;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Payment\PaymentServiceAdapter;
use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали HealthAndSports.
 *
 * Координирует товары для здоровья и спорта, включая Fitness и SportsNutrition.
 */
final readonly class HealthAndSportsService
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly PaymentServiceAdapter $paymentAdapter,
        private readonly FraudMLCoordinatorService $fraudMLCoordinator,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Подготовить чекаут для товаров здоровья и спорта.
     *
     * @param  array{items: array, seller_address: string, buyer_address: string, sub_vertical?: string}  $cartData
     * @return array{delivery_cost: int, eta: int}
     */
    public function prepareCheckout(array $cartData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('HealthAndSports checkout preparation started', [
            'correlation_id' => $correlationId,
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
        ]);

        // Расчёт доставки
        $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
            'vertical' => 'health_and_sports',
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
            'seller_address' => $cartData['seller_address'],
            'buyer_address' => $cartData['buyer_address'],
            'items' => $cartData['items'] ?? [],
        ]);

        return [
            'delivery_cost' => $deliveryCalculation['cost'],
            'eta' => $deliveryCalculation['eta'],
            'distance' => $deliveryCalculation['distance'],
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать заказ.
     *
     * @param  array{user_id: int, items: array, seller_address: string, buyer_address: string, sub_vertical?: string, amount: int}  $orderData
     * @return array{order_id: int, payment_intent_id: string}
     */
    public function createOrder(array $orderData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('HealthAndSports order creation started', [
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
                'vertical' => 'health_and_sports',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            // Расчёт доставки
            $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'health_and_sports',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'seller_address' => $orderData['seller_address'],
                'buyer_address' => $orderData['buyer_address'],
                'items' => $orderData['items'] ?? [],
            ]);

            // Создание заказа
            $orderId = $this->db->table('health_and_sports_orders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id' => $orderData['user_id'],
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'status' => 'pending',
                'total_amount' => $orderData['amount'] ?? 0,
                'delivery_cost' => $deliveryCalculation['cost'],
                'delivery_address' => $orderData['buyer_address'],
                'fraud_score' => $fraudResult['score'] ?? 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            // Подготовка платежа
            $paymentPreparation = $this->paymentAdapter->preparePayment([
                'amount' => $orderData['amount'] ?? 0 + $deliveryCalculation['cost'],
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'health_and_sports',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'payable_type' => 'health_and_sports_order',
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
}
