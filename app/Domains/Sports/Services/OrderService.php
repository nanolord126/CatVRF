<?php declare(strict_types=1);

namespace App\Domains\Sports\Services;

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
        // Sports vertical: 15% for B2C, 12% for B2B
        $rate = $isB2B ? 0.12 : 0.15;
        return (int) ($total * $rate);
    }

    public function validateOrder(array $data, string $correlationId): array
    {
        $fraudScore = $this->fraudService->check($data, $correlationId);
        
        if ($fraudScore > 75) {
            $this->logger->warning('Sports order rejected due to high fraud score', [
                'fraud_score' => $fraudScore,
                'correlation_id' => $correlationId,
            ]);
            
            return ['valid' => false, 'reason' => 'high_fraud_risk', 'fraud_score' => $fraudScore];
        }

        return ['valid' => true, 'fraud_score' => $fraudScore];
    }

    public function processPayment(int $userId, int $tenantId, int $amount, string $paymentMethod, string $correlationId): bool
    {
        $wallet = \App\Models\Wallet::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();
        
        if ($wallet === null) {
            $this->logger->error('Wallet not found for payment', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        try {
            $this->atomicWallet->debit(
                walletId: $wallet->id,
                amount: $amount,
                type: \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL,
                correlationId: $correlationId,
                sourceType: 'sports_order',
                sourceId: null,
            );
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Payment processing failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }
    }

    public function sendOrderConfirmation(int $userId, int $orderId, string $correlationId): void
    {
        $this->notificationService->send($userId, 'order_confirmation', [
            'order_id' => $orderId,
            'vertical' => 'sports',
        ], $correlationId);
    }

    public function getDeliveryEstimate(string $address): string
    {
        // Sports: service-based, instant booking
        return 'Instant digital booking';
    }
}
