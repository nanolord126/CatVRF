<?php

declare(strict_types=1);

namespace App\Domains\Garden\Services;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Payment\PaymentServiceAdapter;
use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали Garden.
 *
 * Координирует товары для сада и огорода с учётом сезонности
 * и крупногабаритной доставки.
 */
final readonly class GardenService
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly PaymentServiceAdapter $paymentAdapter,
        private readonly FraudMLCoordinatorService $fraudMLCoordinator,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Подготовить чекаут с учётом сезонности.
     *
     * @param  array{items: array, seller_address: string, buyer_address: string}  $cartData
     * @return array{delivery_cost: int, eta: int, seasonal_discount: float}
     */
    public function prepareCheckout(array $cartData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Garden checkout preparation started', [
            'correlation_id' => $correlationId,
        ]);

        // Проверка сезонности
        $seasonalDiscount = $this->calculateSeasonalDiscount($cartData['items'] ?? []);

        // Расчёт доставки (крупногабаритные товары)
        $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
            'vertical' => 'garden',
            'seller_address' => $cartData['seller_address'],
            'buyer_address' => $cartData['buyer_address'],
            'items' => $cartData['items'] ?? [],
            'heavy_cargo' => true, // Садовые товары часто крупногабаритные
        ]);

        return [
            'delivery_cost' => $deliveryCalculation['cost'],
            'eta' => $deliveryCalculation['eta'],
            'distance' => $deliveryCalculation['distance'],
            'seasonal_discount' => $seasonalDiscount,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать заказ.
     *
     * @param  array{user_id: int, items: array, seller_address: string, buyer_address: string, amount: int}  $orderData
     * @return array{order_id: int, payment_intent_id: string}
     */
    public function createOrder(array $orderData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Garden order creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $orderData['user_id'],
        ]);

        return $this->db->transaction(function () use ($orderData, $correlationId) {
            // Fraud ML check
            $fraudResult = $this->fraudMLCoordinator->checkPaymentRisk([
                'amount' => $orderData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'garden',
                'correlation_id' => $correlationId,
            ]);

            // Расчёт доставки
            $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'garden',
                'seller_address' => $orderData['seller_address'],
                'buyer_address' => $orderData['buyer_address'],
                'items' => $orderData['items'] ?? [],
                'heavy_cargo' => true,
            ]);

            // Создание заказа
            $orderId = $this->db->table('garden_orders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id' => $orderData['user_id'],
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
                'vertical' => 'garden',
                'payable_type' => 'garden_order',
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
     * Рассчитать сезонную скидку.
     */
    private function calculateSeasonalDiscount(array $items): float
    {
        $currentMonth = now()->month;
        
        // Весна (март-май) - скидки на рассаду и семена
        if ($currentMonth >= 3 && $currentMonth <= 5) {
            return 0.15; // 15% скидка
        }
        
        // Осень (сентябрь-ноябрь) - скидки на инвентарь
        if ($currentMonth >= 9 && $currentMonth <= 11) {
            return 0.10; // 10% скидка
        }
        
        return 0.0;
    }
}
