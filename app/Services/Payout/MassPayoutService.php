<?php declare(strict_types=1);

namespace App\Services\Payout;

use App\Models\Wallet;
use App\Services\FraudControlService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final readonly class MassPayoutService
{
    private const MAX_BATCH_SIZE = 100;
    private const RATE_LIMIT_KEY = 'mass_payout';
    private const RATE_LIMIT_MAX = 10; // 10 массовых выплат в час

    public function __construct(
        private FraudControlService $fraudControl,
        private WalletService $walletService,
    ) {}

    /**
     * Массовая выплата с batch processing
     *
     * @param array $payouts [['wallet_id' => 1, 'amount' => 1000, 'reason' => '...'], ...]
     */
    public function processBatch(
        array $payouts,
        int $tenantId,
        ?string $correlationId = null,
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        if (count($payouts) > self::MAX_BATCH_SIZE) {
            throw new \InvalidArgumentException("Batch size exceeds maximum of " . self::MAX_BATCH_SIZE);
        }

        // Rate limiting
        $rateLimitKey = self::RATE_LIMIT_KEY . ":{$tenantId}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::RATE_LIMIT_MAX)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            Log::channel('fraud_alert')->warning('Mass payout rate limit exceeded', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'available_in_seconds' => $seconds,
            ]);

            throw new \RuntimeException("Too many mass payout requests. Try again in {$seconds} seconds.");
        }

        RateLimiter::hit($rateLimitKey, 3600); // 1 час

        $results = [
            'success' => [],
            'failed' => [],
            'total_amount' => 0,
        ];

        Log::channel('audit')->info('Mass payout batch started', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'payouts_count' => count($payouts),
        ]);

        foreach ($payouts as $payout) {
            try {
                $this->processSinglePayout(
                    walletId: $payout['wallet_id'],
                    amount: $payout['amount'],
                    reason: $payout['reason'] ?? 'Mass payout',
                    correlationId: $correlationId,
                    tenantId: $tenantId,
                );

                $results['success'][] = $payout['wallet_id'];
                $results['total_amount'] += $payout['amount'];
            } catch (\Exception $e) {
                Log::channel('audit')->error('Mass payout single item failed', [
                    'correlation_id' => $correlationId,
                    'wallet_id' => $payout['wallet_id'],
                    'error' => $e->getMessage(),
                ]);

                $results['failed'][] = [
                    'wallet_id' => $payout['wallet_id'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::channel('audit')->info('Mass payout batch completed', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
            'total_amount' => $results['total_amount'],
        ]);

        return $results;
    }

    /**
     * Одиночная выплата с fraud check
     */
    private function processSinglePayout(
        int $walletId,
        int $amount,
        string $reason,
        string $correlationId,
        int $tenantId,
    ): void {
        // Fraud check перед каждой выплатой
        $fraudResult = $this->fraudControl->check(
            userId: $tenantId,
            operationType: 'mass_payout',
            amount: $amount,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Mass payout item blocked by fraud check', [
                'correlation_id' => $correlationId,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'fraud_score' => $fraudResult['score'],
            ]);

            throw new \RuntimeException('Payout blocked by fraud detection system');
        }

        // Проверка существования кошелька
        $wallet = Wallet::findOrFail($walletId);

        if ($wallet->current_balance < $amount) {
            throw new \RuntimeException("Insufficient balance for payout. Required: {$amount}, Available: {$wallet->current_balance}");
        }

        // Списание через WalletService
        $this->walletService->debit(
            tenantId: $wallet->tenant_id,
            amount: $amount,
            type: 'payout',
            sourceId: null,
            correlationId: $correlationId,
            reason: $reason,
            sourceType: 'mass_payout',
            walletId: $walletId,
        );

        Log::channel('audit')->info('Mass payout single item processed', [
            'correlation_id' => $correlationId,
            'wallet_id' => $walletId,
            'amount' => $amount,
            'fraud_score' => $fraudResult['score'],
        ]);
    }

    /**
     * Расчёт комиссии для выплаты
     */
    public function calculateCommission(int $amount, int $tenantId): int
    {
        // Стандартная комиссия 14%
        $commissionPercent = 14;

        return (int) floor($amount * $commissionPercent / 100);
    }
}
