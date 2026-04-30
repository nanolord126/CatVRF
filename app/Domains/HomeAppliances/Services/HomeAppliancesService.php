<?php

declare(strict_types=1);

namespace App\Domains\HomeAppliances\Services;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Realtime\RealtimeTrackingAdapter;
use App\Domains\Shared\Payment\PaymentServiceAdapter;
use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали HomeAppliances.
 *
 * Координирует крупную бытовую технику с доставкой и установкой.
 */
final readonly class HomeAppliancesService
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
     * Подготовить чекаут с учётом доставки и установки.
     *
     * @param  array{items: array, seller_address: string, buyer_address: string, installation_required: bool}  $cartData
     * @return array{delivery_cost: int, eta: int, installation_cost: int}
     */
    public function prepareCheckout(array $cartData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('HomeAppliances checkout preparation started', [
            'correlation_id' => $correlationId,
        ]);

        // Расчёт доставки (крупногабаритная техника)
        $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
            'vertical' => 'home_appliances',
            'seller_address' => $cartData['seller_address'],
            'buyer_address' => $cartData['buyer_address'],
            'items' => $cartData['items'] ?? [],
            'heavy_cargo' => true,
            'requires_assembly' => true,
        ]);

        // Стоимость установки
        $installationCost = ($cartData['installation_required'] ?? false) ? 2000 : 0;

        return [
            'delivery_cost' => $deliveryCalculation['cost'],
            'eta' => $deliveryCalculation['eta'],
            'distance' => $deliveryCalculation['distance'],
            'installation_cost' => $installationCost,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать заказ с доставкой и установкой.
     *
     * @param  array{user_id: int, items: array, seller_address: string, buyer_address: string, installation_required: bool, amount: int}  $orderData
     * @return array{order_id: int, payment_intent_id: string}
     */
    public function createOrder(array $orderData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('HomeAppliances order creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $orderData['user_id'],
        ]);

        return $this->db->transaction(function () use ($orderData, $correlationId) {
            // Fraud ML check
            $fraudResult = $this->fraudMLCoordinator->checkPaymentRisk([
                'amount' => $orderData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'home_appliances',
                'correlation_id' => $correlationId,
            ]);

            // Расчёт доставки
            $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'home_appliances',
                'seller_address' => $orderData['seller_address'],
                'buyer_address' => $orderData['buyer_address'],
                'items' => $orderData['items'] ?? [],
                'heavy_cargo' => true,
                'requires_assembly' => true,
            ]);

            $installationCost = ($orderData['installation_required'] ?? false) ? 2000 : 0;

            // Создание заказа
            $orderId = $this->db->table('home_appliances_orders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id' => $orderData['user_id'],
                'status' => 'pending',
                'total_amount' => $orderData['amount'] ?? 0,
                'delivery_cost' => $deliveryCalculation['cost'],
                'installation_cost' => $installationCost,
                'delivery_address' => $orderData['buyer_address'],
                'installation_required' => $orderData['installation_required'] ?? false,
                'fraud_score' => $fraudResult['score'] ?? 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            // Подготовка платежа
            $paymentPreparation = $this->paymentAdapter->preparePayment([
                'amount' => ($orderData['amount'] ?? 0) + $deliveryCalculation['cost'] + $installationCost,
                'currency' => 'RUB',
                'user_id' => $orderData['user_id'],
                'vertical' => 'home_appliances',
                'payable_type' => 'home_appliances_order',
                'payable_id' => $orderId,
            ]);

            // Запуск реалтайм-трекинга
            $trackingSession = $this->trackingAdapter->startTracking([
                'order_id' => $orderId,
                'vertical' => 'home_appliances',
                'courier_id' => null,
                'buyer_id' => $orderData['user_id'],
            ], $correlationId);

            return [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentPreparation['payment_intent_id'],
                'client_secret' => $paymentPreparation['client_secret'],
                'tracking_session' => $trackingSession,
                'correlation_id' => $correlationId,
            ];
        });
    }
}
