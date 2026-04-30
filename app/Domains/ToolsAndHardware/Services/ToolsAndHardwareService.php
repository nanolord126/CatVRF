<?php

declare(strict_types=1);

namespace App\Domains\ToolsAndHardware\Services;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Payment\PaymentServiceAdapter;
use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали ToolsAndHardware.
 *
 * Координирует инструменты и оборудование (power_tools, hand_tools, hardware).
 * Является под-вертикалью Construction.
 */
final readonly class ToolsAndHardwareService
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly PaymentServiceAdapter $paymentAdapter,
        private readonly FraudMLCoordinatorService $fraudMLCoordinator,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Подготовить чекаут для инструментов и оборудования.
     *
     * @param  array{items: array, seller_address: string, buyer_address: string, sub_vertical?: string}  $cartData
     * @return array{delivery_cost: int, eta: int, heavy_cargo_surcharge: int}
     */
    public function prepareCheckout(array $cartData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('ToolsAndHardware checkout preparation started', [
            'correlation_id' => $correlationId,
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
        ]);

        $heavyCargoSurcharge = $this->calculateHeavyCargoSurcharge($cartData['items'] ?? []);

        $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
            'vertical' => 'tools_and_hardware',
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
            'seller_address' => $cartData['seller_address'],
            'buyer_address' => $cartData['buyer_address'],
            'items' => $cartData['items'] ?? [],
            'heavy_cargo' => true,
        ]);

        return [
            'delivery_cost' => $deliveryCalculation['cost'] + $heavyCargoSurcharge,
            'eta' => $deliveryCalculation['eta'],
            'distance' => $deliveryCalculation['distance'],
            'heavy_cargo_surcharge' => $heavyCargoSurcharge,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать заказ инструментов и оборудования.
     *
     * @param  array{user_id: int, items: array, seller_address: string, buyer_address: string, sub_vertical?: string, amount: int}  $orderData
     * @return array{order_id: int, payment_intent_id: string}
     */
    public function createOrder(array $orderData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('ToolsAndHardware order creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $orderData['user_id'],
            'sub_vertical' => $orderData['sub_vertical'] ?? null,
        ]);

        return $this->db->transaction(function () use ($orderData, $correlationId) {
            $fraudResult = $this->fraudMLCoordinator->checkPaymentRisk([
                'amount' => $orderData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'tools_and_hardware',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            $heavyCargoSurcharge = $this->calculateHeavyCargoSurcharge($orderData['items'] ?? []);

            $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'tools_and_hardware',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'seller_address' => $orderData['seller_address'],
                'buyer_address' => $orderData['buyer_address'],
                'items' => $orderData['items'] ?? [],
                'heavy_cargo' => true,
            ]);

            $orderId = $this->db->table('tools_and_hardware_orders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id' => $orderData['user_id'],
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'status' => 'pending',
                'total_amount' => $orderData['amount'] ?? 0,
                'delivery_cost' => $deliveryCalculation['cost'] + $heavyCargoSurcharge,
                'delivery_address' => $orderData['buyer_address'],
                'heavy_cargo_surcharge' => $heavyCargoSurcharge,
                'fraud_score' => $fraudResult['score'] ?? 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $paymentPreparation = $this->paymentAdapter->preparePayment([
                'amount' => ($orderData['amount'] ?? 0) + $deliveryCalculation['cost'] + $heavyCargoSurcharge,
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'tools_and_hardware',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'payable_type' => 'tools_and_hardware_order',
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
        
        if ($totalWeight > 500) {
            return (int) (ceil(($totalWeight - 500) / 100) * 500);
        }
        
        return 0;
    }
}
