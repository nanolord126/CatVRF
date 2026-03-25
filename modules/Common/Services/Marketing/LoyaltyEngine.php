<?php

namespace App\Domains\Common\Services\Marketing;

use App\Models\User;
use App\Domains\Finances\Services\WalletService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Throwable;

class LoyaltyEngine
{
    private string $correlationId;
    private ?string $tenantId;

    public function __construct(private WalletService $wallet)
    {
        $this->correlationId = Str::uuid();
        $this->tenantId = $this->auth->guard('tenant')?->id();
    }

    /**
     * Фиксированный кэшбэк на основе маржинальности платформы.
     */
    public function calculateCashback(User $user, float $orderAmount): float
    {
        $this->correlationId = Str::uuid();

        try {
            $this->log->channel('marketing')->info('LoyaltyEngine: calculating cashback', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'order_amount' => $orderAmount,
            ]);

            $cashbackRate = 0.01; // 1.0% фиксированно
            $cashback = $orderAmount * $cashbackRate;

            if ($cashback <= 0) {
                throw new \InvalidArgumentException('Cashback amount must be greater than 0');
            }

            // Прямое начисление на кошелек
            $this->wallet->credit($user, $cashback, "Loyalty Cashback (Fixed 1%)");

            Audit$this->log->create([
                'entity_type' => 'LoyaltyCashback',
                'entity_id' => $user->id,
                'action' => 'cashback_credited',
                'user_id' => $this->auth->id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'user_id' => $user->id,
                    'order_amount' => $orderAmount,
                    'cashback_rate' => $cashbackRate,
                    'cashback_amount' => $cashback,
                ],
            ]);

            $this->log->channel('marketing')->info('LoyaltyEngine: cashback calculated', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'cashback' => $cashback,
            ]);

            return $cashback;
        } catch (Throwable $e) {
            $this->log->error('LoyaltyEngine: cashback calculation failed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Определение грейда лояльности на основе накопленного объема покупок.
     */
    public function getUserLoyaltyTier(User $user): string
    {
        try {
            return $this->cache->remember("user_tier_{$user->id}", 86400, function() use ($user) {
                $totalSpent = $user->ledger()?->where('type', 'debit')->sum('amount') ?? 0;

                if ($totalSpent > 100000) return 'platinum';
                if ($totalSpent > 50000) return 'gold';
                if ($totalSpent > 10000) return 'regular';
                return 'newbie';
            });
        } catch (Throwable $e) {
            $this->log->error('LoyaltyEngine: tier calculation failed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return 'newbie';
        }
    }

    /**
     * Ревью-бонус: Начисление за фото/видео отзыв.
     */
    public function rewardReview(User $user, int $mediaCount): void
    {
        $this->correlationId = Str::uuid();

        try {
            $this->log->channel('marketing')->info('LoyaltyEngine: rewarding review', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'media_count' => $mediaCount,
            ]);

            $bonus = $mediaCount > 1 ? 150.0 : 50.0;

            $this->wallet->credit($user, $bonus, "Review Reward: Media incentive");

            Audit$this->log->create([
                'entity_type' => 'ReviewReward',
                'entity_id' => $user->id,
                'action' => 'reward_credited',
                'user_id' => $this->auth->id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'user_id' => $user->id,
                    'media_count' => $mediaCount,
                    'bonus_amount' => $bonus,
                ],
            ]);

            $this->log->channel('marketing')->info('LoyaltyEngine: review reward credited', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'bonus' => $bonus,
            ]);
        } catch (Throwable $e) {
            $this->log->error('LoyaltyEngine: review reward failed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
        }
    }
}
