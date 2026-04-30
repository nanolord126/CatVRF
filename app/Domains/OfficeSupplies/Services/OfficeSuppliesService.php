<?php

declare(strict_types=1);

namespace App\Domains\OfficeSupplies\Services;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Payment\PaymentServiceAdapter;
use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали OfficeSupplies.
 *
 * Координирует канцелярские товары и офисное оборудование (stationery, office_equipment, paper_products).
 * Включает B2B-часть.
 */
final readonly class OfficeSuppliesService
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly PaymentServiceAdapter $paymentAdapter,
        private readonly FraudMLCoordinatorService $fraudMLCoordinator,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Подготовить чекаут для офисных принадлежностей.
     *
     * @param  array{items: array, seller_address: string, buyer_address: string, sub_vertical?: string, is_b2b: bool}  $cartData
     * @return array{delivery_cost: int, eta: int, volume_discount: float}
     */
    public function prepareCheckout(array $cartData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('OfficeSupplies checkout preparation started', [
            'correlation_id' => $correlationId,
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
            'is_b2b' => $cartData['is_b2b'] ?? false,
        ]);

        $volumeDiscount = ($cartData['is_b2b'] ?? false) ? 0.15 : 0.0;

        $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
            'vertical' => 'office_supplies',
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
            'seller_address' => $cartData['seller_address'],
            'buyer_address' => $cartData['buyer_address'],
            'items' => $cartData['items'] ?? [],
        ]);

        return [
            'delivery_cost' => $deliveryCalculation['cost'],
            'eta' => $deliveryCalculation['eta'],
            'distance' => $deliveryCalculation['distance'],
            'volume_discount' => $volumeDiscount,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать заказ офисных принадлежностей.
     *
     * @param  array{user_id: int, items: array, seller_address: string, buyer_address: string, sub_vertical?: string, is_b2b: bool, amount: int}  $orderData
     * @return array{order_id: int, payment_intent_id: string}
     */
    public function createOrder(array $orderData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('OfficeSupplies order creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $orderData['user_id'],
            'sub_vertical' => $orderData['sub_vertical'] ?? null,
            'is_b2b' => $orderData['is_b2b'] ?? false,
        ]);

        return $this->db->transaction(function () use ($orderData, $correlationId) {
            $fraudResult = $this->fraudMLCoordinator->checkPaymentRisk([
                'amount' => $orderData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'office_supplies',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'office_supplies',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'seller_address' => $orderData['seller_address'],
                'buyer_address' => $orderData['buyer_address'],
                'items' => $orderData['items'] ?? [],
            ]);

            $volumeDiscount = ($orderData['is_b2b'] ?? false) ? 0.15 : 0.0;
            $totalAmount = ($orderData['amount'] ?? 0) * (1 - $volumeDiscount);

            $orderId = $this->db->table('office_supplies_orders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id' => $orderData['user_id'],
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'is_b2b' => $orderData['is_b2b'] ?? false,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'delivery_cost' => $deliveryCalculation['cost'],
                'delivery_address' => $orderData['buyer_address'],
                'volume_discount' => $volumeDiscount,
                'fraud_score' => $fraudResult['score'] ?? 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $paymentPreparation = $this->paymentAdapter->preparePayment([
                'amount' => $totalAmount + $deliveryCalculation['cost'],
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'office_supplies',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'payable_type' => 'office_supplies_order',
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
