<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;

use App\Services\FraudControlService;
use App\Services\Payment\WalletService;
use App\Services\CommissionService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

final readonly class OrderService
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly WalletService $walletService,
        private readonly CommissionService $commissionService,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {}

    public function calculateCommission(int $total, bool $isB2B): int
    {
        // Medical vertical: 10% for B2C, 8% for B2B
        $rate = $isB2B ? 0.08 : 0.10;
        return (int) ($total * $rate);
    }

    public function validateOrder(array $data, string $correlationId): array
    {
        $fraudScore = $this->fraudService->check($data, $correlationId);
        
        if ($fraudScore > 90) {
            $this->logger->warning('Medical order rejected due to high fraud score', [
                'fraud_score' => $fraudScore,
                'correlation_id' => $correlationId,
            ]);
            
            return ['valid' => false, 'reason' => 'high_fraud_risk', 'fraud_score' => $fraudScore];
        }

        // Check availability for service-based vertical
        if (isset($data['appointment_date']) && isset($data['service_id'])) {
            $availabilityCheck = $this->checkAvailability($data['service_id'], $data['appointment_date'], $data['appointment_time'] ?? null);
            if (!$availabilityCheck['available']) {
                $this->logger->warning('Medical order rejected due to unavailability', [
                    'service_id' => $data['service_id'],
                    'appointment_date' => $data['appointment_date'],
                    'correlation_id' => $correlationId,
                ]);
                
                return ['valid' => false, 'reason' => 'slot_unavailable', 'available_slots' => $availabilityCheck['available_slots']];
            }
        }

        return ['valid' => true, 'fraud_score' => $fraudScore];
    }

    public function checkAvailability(string $serviceId, string $date, ?string $time = null): array
    {
        // TODO: Implement actual availability check against database
        // Check if the service provider has available slots for the given date/time
        // Return available alternative slots if requested slot is unavailable
        
        return [
            'available' => true,
            'available_slots' => [],
        ];
    }

    public function processPayment(int $userId, int $amount, string $paymentMethod, string $correlationId): bool
    {
        return $this->walletService->deduct($userId, $amount, $paymentMethod, $correlationId);
    }

    public function sendOrderConfirmation(int $userId, int $orderId, string $correlationId): void
    {
        $this->notificationService->send($userId, 'order_confirmation', [
            'order_id' => $orderId,
            'vertical' => 'medical',
        ], $correlationId);
    }

    public function getDeliveryEstimate(string $address): string
    {
        // Medical: service-based, appointment scheduling
        return 'Appointment scheduled within 24-48 hours';
    }
}
