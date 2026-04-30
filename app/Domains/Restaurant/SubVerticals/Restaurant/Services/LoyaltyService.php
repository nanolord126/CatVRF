<?php

declare(strict_types=1);

namespace Modules\Restaurant\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;
use App\Models\User;
use Modules\Restaurant\Models\Guest;
use Modules\Restaurant\Models\LoyaltyProgram;
use Modules\Restaurant\Models\LoyaltyTransaction;
use Modules\Restaurant\Models\Order;
use Modules\Restaurant\Events\LoyaltyBonusEarned;
use Modules\Restaurant\Events\LoyaltyTierChanged;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * Loyalty Service — Сервис для управления бонусами на кошелек (% от заказа)
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 * - Cache::tags for cache invalidation
 * - Audit logging
 */
final readonly class LoyaltyService
{
    use WithAuditLogging;

    public function __construct(
        private readonly AuditService $auditService,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly string $correlationId = 'default',
    ) {}

    /**
     * Начислить бонус за заказ (% от суммы)
     */
    public function earnBonusFromOrder(Order $order): LoyaltyTransaction
    {
        if (!$order->user_id || !$order->restaurant_id) {
            throw new \InvalidArgumentException('Order must have user and restaurant');
        }

        return $this->db->transaction(function () use ($order) {
            $user = $order->user;
            $program = LoyaltyProgram::where('restaurant_id', $order->restaurant_id)
                ->active()
                ->first();

            if (!$program) {
                throw new \InvalidArgumentException('No active loyalty program found');
            }

            // Рассчитываем бонус (% от суммы)
            $bonus = $program->calculateBonus((float) $order->total_amount);
            
            // Применяем множитель уровня
            if ($user->loyalty_tier) {
                $multiplier = $program->getTierBonusMultiplier($user->loyalty_tier);
                $bonus = round($bonus * $multiplier, 2);
            }

            // Начисляем бонус на кошелек
            $transaction = $user->addWalletBonus($bonus, 'earned', "Order #{$order->order_number}");
            $transaction->order_id = $order->id;
            $transaction->order_amount = $order->total_amount;
            $transaction->bonus_percentage = $program->bonus_percentage;
            $transaction->save();

            // Записываем визит
            $user->recordVisit((float) $order->total_amount);

            // Добавляем любимые блюда
            foreach ($order->items as $item) {
                if ($item->menu_item_id) {
                    $user->addToFavorites($item->menu_item_id);
                }
            }

            // Генерируем событие
            event(new LoyaltyBonusEarned($user, $bonus, $order, $this->correlationId));

            // Очищаем кэш
            $this->clearUserCache($user->id);

            return $transaction;
        });
    }

    /**
     * Списать бонус с кошелька для оплаты
     */
    public function redeemWalletBonus(User $user, float $amount, Order $order): LoyaltyTransaction
    {
        return $this->db->transaction(function () use ($user, $amount, $order) {
            $program = LoyaltyProgram::where('restaurant_id', $order->restaurant_id)
                ->active()
                ->first();

            if (!$program) {
                throw new \InvalidArgumentException('No active loyalty program found');
            }

            if ($user->wallet_balance < $amount) {
                throw new \InvalidArgumentException('Insufficient wallet balance');
            }

            // Списываем бонус
            $transaction = $user->redeemWalletBonus($amount, "Redeemed for order #{$order->order_number}");
            $transaction->order_id = $order->id;
            $transaction->order_amount = $order->total_amount;
            $transaction->save();

            // Очищаем кэш
            $this->clearUserCache($user->id);

            $this->logAction('loyalty_bonus_redeemed', 'LoyaltyTransaction', $transaction->id, [
                'user_id' => $user->id,
                'amount' => $amount,
                'order_id' => $order->id,
            ], $user->id, $order->restaurant_id);

            return $transaction;
        });
    }

    /**
     * Начислить бонус (регистрация, день рождения и т.д.)
     */
    public function awardWalletBonus(User $user, float $amount, string $reason): LoyaltyTransaction
    {
        return $this->db->transaction(function () use ($user, $amount, $reason) {
            $transaction = $user->addWalletBonus($amount, 'bonus', $reason);

            // Генерируем событие
            event(new LoyaltyBonusEarned($user, $amount, null, $this->correlationId));

            // Очищаем кэш
            $this->clearUserCache($user->id);

            $this->logCreated('LoyaltyTransaction', $transaction->id, [
                'user_id' => $user->id,
                'amount' => $amount,
                'reason' => $reason,
            ], $user->id, $user->restaurant_id ?? 0);

            return $transaction;
        });
    }

    /**
     * Обновить уровень лояльности гостя
     */
    public function updateUserTier(User $user): User
    {
        $program = LoyaltyProgram::where('restaurant_id', $user->restaurant_id)
            ->active()
            ->first();

        if (!$program) {
            return $user;
        }

        $oldTier = $user->loyalty_tier;
        $user->updateLoyaltyTier();
        $user->save();

        // Если уровень изменился, генерируем событие
        if ($oldTier !== $user->loyalty_tier) {
            event(new LoyaltyTierChanged($user, $oldTier, $user->loyalty_tier, $this->correlationId));

            $this->logUpdated('User', $user->id, [
                'old_tier' => $oldTier,
                'new_tier' => $user->loyalty_tier,
            ], $user->id, $user->restaurant_id ?? 0);
        }

        // Очищаем кэш
        $this->clearUserCache($user->id);

        return $user->fresh();
    }

    /**
     * Получить историю транзакций гостя
     */
    public function getUserTransactions(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return LoyaltyTransaction::where('user_id', $userId)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику программы лояльности
     */
    public function getProgramStatistics(int $programId, ?string $date = null): array
    {
        $date = $date ?? today();
        $program = LoyaltyProgram::find($programId);

        $users = $program->users;
        $transactions = $program->transactions()
            ->whereDate('created_at', $date)
            ->get();

        return [
            'total_users' => $users->count(),
            'active_users' => $users->where('visits_count', '>', 0)->count(),
            'total_bonus_issued' => $transactions->earned()->sum('amount_change'),
            'total_bonus_redeemed' => abs($transactions->redeemed()->sum('amount_change')),
            'total_wallet_balance' => $users->sum('wallet_balance'),
            'bonus_by_tier' => $users->groupBy('loyalty_tier')->map->count(),
            'transactions_by_type' => $transactions->groupBy('type')->map->count(),
        ];
    }

    /**
     * Проверить и начислить бонус за день рождения
     */
    public function checkBirthdayBonus(User $user): ?LoyaltyTransaction
    {
        if (!$user->birth_date) {
            return null;
        }

        $today = now();
        $birthday = $user->birth_date->setYear($today->year);

        if ($birthday->isToday()) {
            $program = LoyaltyProgram::where('restaurant_id', $user->restaurant_id)
                ->active()
                ->first();

            if ($program && $program->birthday_bonus_amount > 0) {
                // Проверяем, не начисляли ли уже сегодня
                $alreadyAwarded = LoyaltyTransaction::where('user_id', $user->id)
                    ->where('type', 'bonus')
                    ->where('description', 'like', '%birthday%')
                    ->whereDate('created_at', today())
                    ->exists();

                if (!$alreadyAwarded) {
                    return $this->awardWalletBonus($user, $program->birthday_bonus_amount, 'Birthday bonus');
                }
            }
        }

        return null;
    }

    /**
     * Истечь просроченные бонусы
     */
    public function expireBonuses(): int
    {
        $expiredCount = LoyaltyTransaction::where('expires_at', '<', now())
            ->where('is_expired', false)
            ->get()
            ->each(function ($transaction) {
                $transaction->markAsExpired();
                
                // Вычитаем бонус из кошелька пользователя
                $user = $transaction->user;
                $user->wallet_balance += $transaction->amount_change; // amount_change отрицательный для истёкших
                $user->save();
            })
            ->count();

        return $expiredCount;
    }

    // ========================
    // PRIVATE METHODS
    // ========================

    private function clearUserCache(int $userId): void
    {
        $this->cache->tags(['user:' . $userId])->flush();
    }
}
