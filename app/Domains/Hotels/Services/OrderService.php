<?php declare(strict_types=1);

namespace App\Domains\Hotels\Services;

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
        // Hotels vertical: 12% for B2C, 10% for B2B
        $rate = $isB2B ? 0.10 : 0.12;
        return (int) ($total * $rate);
    }

    public function validateOrder(array $data, string $correlationId): array
    {
        $fraudScore = $this->fraudService->check($data, $correlationId);
        
        if ($fraudScore > 85) {
            $this->logger->warning('Hotels order rejected due to high fraud score', [
                'fraud_score' => $fraudScore,
                'correlation_id' => $correlationId,
            ]);
            
            return ['valid' => false, 'reason' => 'high_fraud_risk', 'fraud_score' => $fraudScore];
        }

        // Check availability for service-based vertical
        if (isset($data['check_in_date']) && isset($data['check_out_date']) && isset($data['room_type'])) {
            $availabilityCheck = $this->checkAvailability($data['room_type'], $data['check_in_date'], $data['check_out_date'], $data['guests'] ?? 1);
            if (!$availabilityCheck['available']) {
                $this->logger->warning('Hotels order rejected due to unavailability', [
                    'room_type' => $data['room_type'],
                    'check_in' => $data['check_in_date'],
                    'check_out' => $data['check_out_date'],
                    'correlation_id' => $correlationId,
                ]);
                
                return ['valid' => false, 'reason' => 'rooms_unavailable', 'alternative_rooms' => $availabilityCheck['alternative_rooms']];
            }
        }

        return ['valid' => true, 'fraud_score' => $fraudScore];
    }

    public function checkAvailability(string $roomType, string $checkInDate, string $checkOutDate, int $guests): array
    {
        // TODO: Implement actual room availability check against database
        // Check if rooms of the specified type are available for the date range
        // Return alternative room types if requested type is unavailable
        
        return [
            'available' => true,
            'alternative_rooms' => [],
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
            'vertical' => 'hotels',
        ], $correlationId);
    }

    public function getDeliveryEstimate(string $address): string
    {
        // Hotels: service-based, instant digital booking
        return 'Instant digital booking confirmation';
    }
}
