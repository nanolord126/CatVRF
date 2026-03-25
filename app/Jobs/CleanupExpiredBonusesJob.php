<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\Finances\Models\Bonus;
use Modules\Finances\Models\BalanceTransaction;
use Illuminate\Support\Str;

/**
 * Cleanup Expired Bonuses Job
 * CANON 2026 - Production Ready
 *
 * Ежедневная очистка истёкших бонусов.
 * Удаление бонусов, которые прошли срок действия (365 дней по умолчанию).
 * Запускается каждый день в 07:00 UTC.
 */
final class CleanupExpiredBonusesJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 1800; // 30 минут
    public int $tries = 2;

    private readonly string $correlationId;

    public function __construct()
    {
        $this->correlationId = (string) Str::uuid()->toString();
    }

    public function handle(): void
    {
        try {
            $this->log->channel('audit')->info('Cleanup expired bonuses job started', [
                'correlation_id' => $this->correlationId,
                'timestamp' => now()->toIso8601String(),
            ]);

            // 1. Найти все истёкшие бонусы
            $expiredBonuses = Bonus::query()
                ->where('expires_at', '<', now())
                ->where('spent_at', null) // не потрачены
                ->where('withdrawn_at', null) // не выведены
                ->lockForUpdate()
                ->get();

            if ($expiredBonuses->isEmpty()) {
                $this->log->info('No expired bonuses found');
                return;
            }

            $this->log->info('Expired bonuses found', [
                'correlation_id' => $this->correlationId,
                'count' => $expiredBonuses->count(),
            ]);

            // 2. Отправить уведомления перед удалением
            $this->notifyUsers($expiredBonuses);

            // 3. Удалить или архивировать бонусы
            $deletedCount = 0;
            foreach ($expiredBonuses as $bonus) {
                $deletedCount += $this->expireBonus($bonus);
            }

            // 4. Логировать результат
            $this->log->channel('audit')->info('Cleanup expired bonuses job completed', [
                'correlation_id' => $this->correlationId,
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Cleanup expired bonuses job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Отправить уведомление пользователю перед удалением
     */
    private function notifyUsers($expiredBonuses): void
    {
        $userGroups = $expiredBonuses->groupBy('user_id');

        foreach ($userGroups as $userId => $bonuses) {
            $totalAmount = $bonuses->sum('amount');

            // Создать уведомление
            $this->db->table('notifications')->insert([
                'user_id' => $userId,
                'type' => 'bonus.expired',
                'title' => 'Бонусы истекли',
                'message' => "К сожалению, бонусы на сумму {$totalAmount} коп истекли",
                'data' => json_encode([
                    'amount' => $totalAmount,
                    'bonus_count' => $bonuses->count(),
                ]),
                'created_at' => now(),
                'read_at' => null,
            ]);

            $this->log->info('Expiration notification sent', [
                'user_id' => $userId,
                'bonus_count' => $bonuses->count(),
                'total_amount' => $totalAmount,
            ]);
        }
    }

    /**
     * Удалить или архивировать бонус
     */
    private function expireBonus(Bonus $bonus): int
    {
        try {
            $this->db->transaction(function () use ($bonus) {
                // Создать запись в архиве (optional, для аудита)
                $this->db->table('bonus_archive')->insert([
                    'bonus_id' => $bonus->id,
                    'user_id' => $bonus->user_id,
                    'type' => $bonus->type,
                    'amount' => $bonus->amount,
                    'expires_at' => $bonus->expires_at,
                    'expired_at' => now(),
                    'correlation_id' => $this->correlationId,
                    'created_at' => now(),
                ]);

                // Удалить бонус (soft delete если есть)
                $bonus->update([
                    'expired_at' => now(),
                    'deleted_at' => now(), // если используется SoftDeletes
                ]);

                $this->log->info('Bonus expired and archived', [
                    'bonus_id' => $bonus->id,
                    'user_id' => $bonus->user_id,
                    'amount' => $bonus->amount,
                ]);
            });

            return 1;

        } catch (\Exception $e) {
            $this->log->warning('Error expiring bonus', [
                'bonus_id' => $bonus->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    public function failed(\Exception $exception): void
    {
        $this->log->channel('audit')->error('CleanupExpiredBonusesJob failed permanently', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
