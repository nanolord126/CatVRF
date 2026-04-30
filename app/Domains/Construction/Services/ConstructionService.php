<?php

declare(strict_types=1);

namespace App\Domains\Construction\Services;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Payment\PaymentServiceAdapter;
use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали Construction.
 *
 * Координирует строительные материалы с учётом тяжёлых грузов
 * и проектных адресов.
 */
final readonly class ConstructionService
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly PaymentServiceAdapter $paymentAdapter,
        private readonly FraudMLCoordinatorService $fraudMLCoordinator,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Подготовить чекаут для строительных материалов.
     *
     * @param  array{items: array, seller_address: string, buyer_address: string, project_address?: string}  $cartData
     * @return array{delivery_cost: int, eta: int, heavy_cargo_surcharge: int}
     */
    public function prepareCheckout(array $cartData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Construction checkout preparation started', [
            'correlation_id' => $correlationId,
        ]);

        // Расчёт доставки с учётом тяжёлых грузов
        $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
            'vertical' => 'construction',
            'seller_address' => $cartData['seller_address'],
            'buyer_address' => $cartData['project_address'] ?? $cartData['buyer_address'],
            'items' => $cartData['items'] ?? [],
            'heavy_cargo' => true,
            'project_address' => $cartData['project_address'] ?? null,
        ]);

        // Надбавка за тяжёлый груз
        $heavyCargoSurcharge = $this->calculateHeavyCargoSurcharge($cartData['items'] ?? []);

        return [
            'delivery_cost' => $deliveryCalculation['cost'] + $heavyCargoSurcharge,
            'eta' => $deliveryCalculation['eta'],
            'distance' => $deliveryCalculation['distance'],
            'heavy_cargo_surcharge' => $heavyCargoSurcharge,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать заказ строительных материалов.
     *
     * @param  array{user_id: int, items: array, seller_address: string, buyer_address: string, project_address?: string, amount: int}  $orderData
     * @return array{order_id: int, payment_intent_id: string}
     */
    public function createOrder(array $orderData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Construction order creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $orderData['user_id'],
        ]);

        return $this->db->transaction(function () use ($orderData, $correlationId) {
            // Fraud ML check
            $fraudResult = $this->fraudMLCoordinator->checkPaymentRisk([
                'amount' => $orderData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'construction',
                'correlation_id' => $correlationId,
            ]);

            // Расчёт доставки
            $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'construction',
                'seller_address' => $orderData['seller_address'],
                'buyer_address' => $orderData['project_address'] ?? $orderData['buyer_address'],
                'items' => $orderData['items'] ?? [],
                'heavy_cargo' => true,
                'project_address' => $orderData['project_address'] ?? null,
            ]);

            // Создание заказа
            $orderId = $this->db->table('construction_orders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id' => $orderData['user_id'],
                'status' => 'pending',
                'total_amount' => $orderData['amount'] ?? 0,
                'delivery_cost' => $deliveryCalculation['cost'],
                'delivery_address' => $orderData['project_address'] ?? $orderData['buyer_address'],
                'project_address' => $orderData['project_address'] ?? null,
                'fraud_score' => $fraudResult['score'] ?? 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            // Подготовка платежа
            $paymentPreparation = $this->paymentAdapter->preparePayment([
                'amount' => $orderData['amount'] ?? 0 + $deliveryCalculation['cost'],
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'construction',
                'payable_type' => 'construction_order',
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
     * Рассчитать надбавку за тяжёлый груз.
     */
    private function calculateHeavyCargoSurcharge(array $items): int
    {
        $totalWeight = array_sum(array_map(fn ($item) => $item['weight'] ?? 0, $items));
        
        // 500 руб за каждые 100 кг свыше 500 кг
        if ($totalWeight > 500) {
            return (int) (ceil(($totalWeight - 500) / 100) * 500);
        }
        
        return 0;
    }
}
