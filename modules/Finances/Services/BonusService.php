<?php
declare(strict_types=1);

namespace Modules\Finances\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Services\FraudControlService;
use Modules\Finances\Models\Bonus;
use Modules\Finances\Models\BonusTransaction;
use Modules\Payments\Services\WalletService;
use Exception;

final readonly class BonusService
{
    public function __construct(
        private Connection $connection,
        private WalletService $walletService,
        private FraudControlService $fraudControlService,
    ) {}

    /**
     * Начисляет бонус пользователю/бизнесу.
     *
     * @param int $recipientId ID получателя (user_id или tenant_id)
     * @param int $tenantId ID тенанта
     * @param int $amountCopeki Сумма в копейках
     * @param string $type Тип: referral_bonus, turnover_bonus, promo, loyalty
     * @param int|null $sourceId ID источника бонуса (referral_id, order_id и т.д.)
     * @param string $sourceType Тип источника: referral, turnover, promo, loyalty, migration
     * @param string $correlationId Идентификатор корреляции
     * @return BonusTransaction
     * @throws Exception
     */
    public function award(
        int $recipientId,
        int $tenantId,
        int $amountCopeki,
        string $type,
        ?int $sourceId,
        string $sourceType,
        string $correlationId = '',
    ): BonusTransaction {
        try {
            // Проверка fraud
            $this->fraudControlService->checkBonus($tenantId, $recipientId, $amountCopeki, $correlationId);

            $this->log->channel('audit')->info('Начисление бонуса', [
                'recipient_id' => $recipientId,
                'tenant_id' => $tenantId,
                'amount' => $amountCopeki,
                'type' => $type,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId,
            ]);

            return $this->db->transaction(function () use (
                $recipientId,
                $tenantId,
                $amountCopeki,
                $type,
                $sourceId,
                $sourceType,
                $correlationId,
            ) {
                // Создаём бонус
                $bonus = Bonus::create([
                    'tenant_id' => $tenantId,
                    'recipient_id' => $recipientId,
                    'amount' => $amountCopeki,
                    'type' => $type,
                    'source_id' => $sourceId,
                    'source_type' => $sourceType,
                    'status' => 'credited',
                    'correlation_id' => $correlationId,
                ]);

                // Зачисляем на wallet
                $this->walletService->creditBonus(
                    $recipientId,
                    $tenantId,
                    $amountCopeki,
                    $type,
                    $correlationId,
                );

                // Создаём транзакцию
                $transaction = BonusTransaction::create([
                    'bonus_id' => $bonus->id,
                    'recipient_id' => $recipientId,
                    'tenant_id' => $tenantId,
                    'amount' => $amountCopeki,
                    'type' => $type,
                    'status' => 'credited',
                    'credited_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Бонус начислен успешно', [
                    'bonus_id' => $bonus->id,
                    'transaction_id' => $transaction->id,
                    'correlation_id' => $correlationId,
                ]);

                return $transaction;
            });
        } catch (Exception $e) {
            $this->log->channel('audit')->error('Ошибка при начислении бонуса', [
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Рефундирует бонус.
     *
     * @param int $bonusId ID бонуса
     * @param string $reason Причина рефунда
     * @param string $correlationId Идентификатор корреляции
     * @return bool
     * @throws Exception
     */
    public function refund(
        int $bonusId,
        string $reason = '',
        string $correlationId = '',
    ): bool {
        try {
            return $this->db->transaction(function () use ($bonusId, $reason, $correlationId) {
                $bonus = Bonus::findOrFail($bonusId);

                // Списываем с wallet
                $this->walletService->debitBonus(
                    $bonus->recipient_id,
                    $bonus->tenant_id,
                    $bonus->amount,
                    $bonus->type,
                    $correlationId,
                );

                // Обновляем статус
                $bonus->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'refund_reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                // Создаём транзакцию рефунда
                BonusTransaction::create([
                    'bonus_id' => $bonus->id,
                    'recipient_id' => $bonus->recipient_id,
                    'tenant_id' => $bonus->tenant_id,
                    'amount' => -$bonus->amount,
                    'type' => $bonus->type,
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Бонус рефундирован', [
                    'bonus_id' => $bonusId,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (Exception $e) {
            $this->log->channel('audit')->error('Ошибка при рефунде бонуса', [
                'bonus_id' => $bonusId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Проверяет и начисляет бонус за достижение оборота.
     *
     * @param int $recipientId ID получателя
     * @param int $tenantId ID тенанта
     * @param int $currentTurnover Текущий оборот в копейках
     * @param string $correlationId Идентификатор корреляции
     * @return BonusTransaction|null
     * @throws Exception
     */
    public function checkAndAwardTurnoverBonus(
        int $recipientId,
        int $tenantId,
        int $currentTurnover,
        string $correlationId = '',
    ): ?BonusTransaction {
        try {
            // 50 000 ₽ = 5 000 000 копеек
            $threshold = 5000000;
            $bonusAmount = 200000; // 2000 ₽

            // Проверяем, не начислен ли уже бонус на этот порог
            $existing = Bonus::where('recipient_id', $recipientId)
                ->where('tenant_id', $tenantId)
                ->where('type', 'turnover_bonus')
                ->where('status', 'credited')
                ->whereRaw('source_id >= ?', [$currentTurnover / 1000000])
                ->exists();

            if ($existing || $currentTurnover < $threshold) {
                return null;
            }

            return $this->award(
                $recipientId,
                $tenantId,
                $bonusAmount,
                'turnover_bonus',
                null,
                'turnover',
                $correlationId,
            );
        } catch (Exception $e) {
            $this->log->channel('audit')->error('Ошибка при проверке бонуса за оборот', [
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
