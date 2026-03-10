<?php

namespace App\Domains\Common\Services\Marketing;

use App\Models\User;
use App\Domains\Finances\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Throwable;

class GamificationService
{
    private string $correlationId;
    private ?string $tenantId;

    public function __construct()
    {
        $this->correlationId = Str::uuid();
        $this->tenantId = Auth::guard('tenant')?->id();
    }

    /**
     * Алгоритм наград за достижения (Микро-цели).
     */
    public function trackWeeklyAchievement(int $userId): void
    {
        $this->correlationId = Str::uuid();

        try {
            $user = User::find($userId);
            if (!$user) {
                throw new \RuntimeException("User {$userId} not found");
            }

            Log::channel('marketing')->info('Gamification: tracking weekly achievement', [
                'correlation_id' => $this->correlationId,
                'user_id' => $userId,
            ]);

            $weeklyOrderCount = PaymentTransaction::where('user_id', $userId)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

            if ($weeklyOrderCount >= 3) {
                $this->applyAchievementReward($user, 'WEEKLY_HERO');
            }

            Log::channel('marketing')->info('Gamification: weekly achievement tracked', [
                'correlation_id' => $this->correlationId,
                'user_id' => $userId,
                'order_count' => $weeklyOrderCount,
            ]);
        } catch (Throwable $e) {
            Log::error('Gamification: weekly achievement tracking failed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
        }
    }

    private function applyAchievementReward(User $user, string $achievementType): void
    {
        try {
            $reward = 250.0;

            Log::channel('marketing')->info('Gamification: applying achievement reward', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'achievement' => $achievementType,
                'reward' => $reward,
            ]);

            // Зачисление на кошелек
            $wallet = $user->wallet;
            if ($wallet) {
                $wallet->increment('balance', $reward);
            }

            // Логирование в audit
            AuditLog::create([
                'entity_type' => 'Achievement',
                'entity_id' => $achievementType,
                'action' => 'reward_applied',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'user_id' => $user->id,
                    'achievement_type' => $achievementType,
                    'reward_amount' => $reward,
                ],
            ]);

            Log::channel('marketing')->info('Gamification: achievement reward applied', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'achievement' => $achievementType,
            ]);
        } catch (Throwable $e) {
            Log::error('Gamification: reward application failed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'achievement' => $achievementType,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
        }
    }
}
