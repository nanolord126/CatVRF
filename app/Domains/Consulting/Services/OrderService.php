<?php declare(strict_types=1);

namespace App\Domains\Consulting\Services;

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
        $rate = $isB2B ? 0.12 : 0.15;
        return (int) ($total * $rate);
    }

    public function validateOrder(array $data, string $correlationId): array
    {
        $fraudScore = $this->fraudService->check($data, $correlationId);
        
        if ($fraudScore > 85) {
            $this->logger->warning('Consulting order rejected due to high fraud score', [
                'fraud_score' => $fraudScore,
                'correlation_id' => $correlationId,
            ]);
            
            return ['valid' => false, 'reason' => 'high_fraud_risk', 'fraud_score' => $fraudScore];
        }

        return ['valid' => true, 'fraud_score' => $fraudScore];
    }

    public function processPayment(int $userId, int $amount, string $paymentMethod, string $correlationId): bool
    {
        return $this->walletService->deduct($userId, $amount, $paymentMethod, $correlationId);
    }

    public function sendOrderConfirmation(int $userId, int $orderId, string $correlationId): void
    {
        $this->notificationService->send($userId, 'order_confirmation', [
            'order_id' => $orderId,
            'vertical' => 'consulting',
        ], $correlationId);
    }

    public function getDeliveryEstimate(string $address): string
    {
        // Consulting: service-based, digital consultation scheduling
        return 'Digital consultation scheduled within 24-72 hours';
    }
}
