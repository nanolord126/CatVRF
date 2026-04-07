<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Services;

use App\Services\WalletService;
use Illuminate\Support\Facades\Log;

final class WalletIntegrationService
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    public function creditBonus(int $userId, int $amount, string $correlationId): void
    {
        try {
            $wallet = $this->walletService->getUserWallet($userId);
            $this->walletService->credit($wallet->id, $amount, 'bonus', $correlationId);

            Log::channel('audit')->info('Bonus credited to wallet.', [
                'user_id' => $userId,
                'amount' => $amount,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to credit bonus to wallet.', [
                'user_id' => $userId,
                'amount' => $amount,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
