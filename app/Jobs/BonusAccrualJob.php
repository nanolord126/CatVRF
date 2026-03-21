<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\Finances\Services\BonusService;
use Modules\Finances\Models\Bonus;
use Modules\Marketplace\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Bonus Accrual Job
 * CANON 2026 - Production Ready
 *
 * Ежемесячный пересчёт бонусов по обороту.
 * Начисление бонусов за turnover и loyalty.
 * Запускается в первый день месяца в 06:00 UTC.
 */
final class BonusAccrualJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 3600; // 1 час
    public int $tries = 2;

    private readonly BonusService $bonusService;
    private readonly string $correlationId;

    public function __construct()
    {
        $this->bonusService = app(BonusService::class);
        $this->correlationId = (string) Str::uuid();
    }

    public function handle(): void
    {
        try {
            Log::channel('audit')->info('Bonus accrual job started', [
                'correlation_id' => $this->correlationId,
                'timestamp' => now()->toIso8601String(),
            ]);

            $lastMonth = now()->subMonth();

            // 1. Получить всех пользователей с заказами за последний месяц
            $users = Order::query()
                ->where('status', 'completed')
                ->where('paid_at', '>=', $lastMonth->startOfMonth())
                ->where('paid_at', '<=', $lastMonth->endOfMonth())
                ->distinct()
                ->pluck('user_id');

            if ($users->isEmpty()) {
                Log::info('No users with completed orders found for bonus accrual');
                return;
            }

            Log::info('Starting bonus accrual for users', [
                'correlation_id' => $this->correlationId,
                'user_count' => $users->count(),
            ]);

            // 2. Для каждого пользователя рассчитать и начислить бонусы
            $bonusesCreated = 0;
            foreach ($users as $userId) {
                $bonusesCreated += $this->accrueUserBonuses($userId, $lastMonth);
            }

            Log::channel('audit')->info('Bonus accrual job completed', [
                'correlation_id' => $this->correlationId,
                'bonuses_created' => $bonusesCreated,
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Bonus accrual job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Начислить бонусы конкретному пользователю
     */
    private function accrueUserBonuses(int $userId, Carbon $month): int
    {
        $bonusesCreated = 0;

        try {
            // Получить все завершённые заказы за месяц
            $orders = Order::query()
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->where('paid_at', '>=', $month->startOfMonth())
                ->where('paid_at', '<=', $month->endOfMonth())
                ->get();

            if ($orders->isEmpty()) {
                return 0;
            }

            // Рассчитать оборот (сумму успешных платежей)
            $monthlyTurnover = $orders->sum(DB::raw('total_price - refunded_amount'));

            // 1. Бонус за оборот (turnover bonus)
            $bonusesCreated += $this->accrueOverBonuses($userId, $monthlyTurnover, $month);

            // 2. Бонус за лояльность (loyalty bonus)
            $bonusesCreated += $this->accrueLoyaltyBonuses($userId, $orders->count(), $month);

            return $bonusesCreated;

        } catch (\Exception $e) {
            Log::warning('Error accruing bonuses for user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Начислить бонусы за оборот
     * Каждые 50,000 коп = 500 руб → 200,000 коп бонус (2,000 руб)
     */
    private function accrueOverBonuses(int $userId, int $turnover, Carbon $month): int
    {
        $turnoverThreshold = (int) config('bonuses.turnover.threshold', 5000000); // 50,000 RUB в коп
        $bonusAmount = (int) config('bonuses.turnover.bonus_amount', 200000); // 2,000 RUB в коп

        if ($turnover < $turnoverThreshold) {
            return 0; // Не прошли порог
        }

        // Количество полных порогов
        $bonusCount = intdiv($turnover, $turnoverThreshold);

        // Проверить, не начислили ли уже этот бонус в этом месяце
        $existingBonus = Bonus::query()
            ->where('user_id', $userId)
            ->where('type', 'turnover_bonus')
            ->where('created_at', '>=', $month->startOfMonth())
            ->where('created_at', '<=', $month->endOfMonth())
            ->count();

        if ($existingBonus > 0) {
            return 0; // Уже начислили
        }

        // Начислить бонус за каждый полный порог
        for ($i = 0; $i < $bonusCount; $i++) {
            DB::transaction(function () use ($userId, $bonusAmount, $month) {
                Bonus::create([
                    'user_id' => $userId,
                    'type' => 'turnover_bonus',
                    'amount' => $bonusAmount,
                    'expires_at' => now()->addYear(),
                    'accrued_at' => now(),
                    'correlation_id' => $this->correlationId,
                    'comment' => "Turnover bonus for {$month->format('F Y')}",
                ]);

                Log::info('Turnover bonus accrued', [
                    'user_id' => $userId,
                    'amount' => $bonusAmount,
                    'month' => $month->format('Y-m'),
                ]);
            });
        }

        return $bonusCount;
    }

    /**
     * Начислить бонусы за лояльность
     * 0.5% от суммы каждой покупки
     */
    private function accrueLoyaltyBonuses(int $userId, int $orderCount, Carbon $month): int
    {
        $loyaltyPercentage = (float) config('bonuses.loyalty.percentage', 0.5); // 0.5%

        // Получить сумму всех заказов
        $totalAmount = Order::query()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->where('paid_at', '>=', $month->startOfMonth())
            ->where('paid_at', '<=', $month->endOfMonth())
            ->sum('total_price');

        if ($totalAmount <= 0) {
            return 0;
        }

        // Рассчитать бонус
        $bonusAmount = (int) ($totalAmount * ($loyaltyPercentage / 100));

        if ($bonusAmount < 1000) { // Минимум 10 руб
            return 0;
        }

        // Проверить, не начислили ли уже в этом месяце
        $existingBonus = Bonus::query()
            ->where('user_id', $userId)
            ->where('type', 'loyalty_bonus')
            ->where('created_at', '>=', $month->startOfMonth())
            ->where('created_at', '<=', $month->endOfMonth())
            ->count();

        if ($existingBonus > 0) {
            return 0;
        }

        DB::transaction(function () use ($userId, $bonusAmount, $month) {
            Bonus::create([
                'user_id' => $userId,
                'type' => 'loyalty_bonus',
                'amount' => $bonusAmount,
                'expires_at' => now()->addYear(),
                'accrued_at' => now(),
                'correlation_id' => $this->correlationId,
                'comment' => "Loyalty bonus for {$month->format('F Y')}",
            ]);

            Log::info('Loyalty bonus accrued', [
                'user_id' => $userId,
                'amount' => $bonusAmount,
                'month' => $month->format('Y-m'),
            ]);
        });

        return 1;
    }

    public function failed(\Exception $exception): void
    {
        Log::channel('audit')->error('BonusAccrualJob failed permanently', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
