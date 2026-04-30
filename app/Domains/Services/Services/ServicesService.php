<?php

declare(strict_types=1);

namespace App\Domains\Services\Services;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Payment\PaymentServiceAdapter;
use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали Services.
 *
 * Координирует B2C услуги (Consulting и другие).
 */
final readonly class ServicesService
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly PaymentServiceAdapter $paymentAdapter,
        private readonly FraudMLCoordinatorService $fraudMLCoordinator,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Подготовить заказ услуги.
     *
     * @param  array{service_id: int, start_date: string, location: string}  $serviceData
     * @return array{total_cost: int, availability: bool}
     */
    public function prepareService(array $serviceData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Services preparation started', [
            'correlation_id' => $correlationId,
            'service_id' => $serviceData['service_id'] ?? null,
        ]);

        $availability = $this->checkAvailability($serviceData);
        $totalCost = $this->calculateServiceCost($serviceData);

        return [
            'total_cost' => $totalCost,
            'availability' => $availability,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать заказ услуги.
     *
     * @param  array{user_id: int, service_id: int, start_date: string, location: string, amount: int}  $serviceData
     * @return array{service_order_id: int, payment_intent_id: string}
     */
    public function createServiceOrder(array $serviceData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Services order creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $serviceData['user_id'],
            'service_id' => $serviceData['service_id'] ?? null,
        ]);

        return $this->db->transaction(function () use ($serviceData, $correlationId) {
            $fraudResult = $this->fraudMLCoordinator->checkPaymentRisk([
                'amount' => $serviceData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $serviceData['user_id'],
                'vertical' => 'services',
                'correlation_id' => $correlationId,
            ]);

            $serviceOrderId = $this->db->table('services_orders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id' => $serviceData['user_id'],
                'service_id' => $serviceData['service_id'],
                'start_date' => $serviceData['start_date'],
                'location' => $serviceData['location'],
                'status' => 'pending',
                'total_amount' => $serviceData['amount'] ?? 0,
                'fraud_score' => $fraudResult['score'] ?? 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $paymentPreparation = $this->paymentAdapter->preparePayment([
                'amount' => $serviceData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $serviceData['user_id'],
                'vertical' => 'services',
                'payable_type' => 'services_order',
                'payable_id' => $serviceOrderId,
            ]);

            return [
                'service_order_id' => $serviceOrderId,
                'payment_intent_id' => $paymentPreparation['payment_intent_id'],
                'client_secret' => $paymentPreparation['client_secret'],
                'correlation_id' => $correlationId,
            ];
        });
    }

    private function checkAvailability(array $serviceData): bool
    {
        return true;
    }

    private function calculateServiceCost(array $serviceData): int
    {
        return 5000;
    }
}
