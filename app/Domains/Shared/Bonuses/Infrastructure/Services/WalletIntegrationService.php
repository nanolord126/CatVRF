<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Services;

use App\Services\WalletService;
use Illuminate\Log\LogManager;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

final class WalletIntegrationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly WalletService $walletService,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    public function creditBonus(int $userId, int $amount, string $correlationId): void
    {
        try {
            $wallet = $this->walletService->getUserWallet($userId);
            $this->walletService->credit($wallet->id, $amount, 'bonus', $correlationId);

            $this->log->channel('audit')->info('Bonus credited to wallet.', [
                'user_id' => $userId,
                'amount' => $amount,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to credit bonus to wallet.', [
                'user_id' => $userId,
                'amount' => $amount,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
