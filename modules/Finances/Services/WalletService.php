<?php

namespace App\Domains\Finances\Services;

use App\Models\User;
use Illuminate\Support\Facades\{DB, Log};
use Exception;

/**
 * Сервис управления кошельком пользователя.
 * 
 * Поддерживает:
 * - Зачисление средств (credit)
 * - Списание средств (debit) с проверкой баланса
 * - Трансферы между пользователями
 * - Логирование всех операций в ledger
 */
class WalletService
{
    /**
     * Зачислить средства на кошелёк.
     * 
     * @param User $user Пользователь
     * @param float $amount Сумма
     * @param string $reason Причина (для логирования)
     * @param string|null $reference Ссылка на операцию (платёж, заказ и т.д.)
     */
    public function credit(User $user, float $amount, string $reason, string $reference = null): void
    {
        DB::transaction(function () use ($user, $amount, $reason, $reference) {
            if ($amount <= 0) {
                throw new Exception('Amount must be greater than 0');
            }

            // Блокируем кошелек для предотвращения race condition (Audit Fix 2026)
            $wallet = $user->wallet()->lockForUpdate()->first();

            if (!$wallet) {
                $wallet = $user->wallet()->create(['balance' => 0]);
            }

            // Увеличить баланс кошелька
            $wallet->increment('balance', $amount);

            // Логировать операцию в ledger
            $user->ledger()->create([
                'type' => 'credit',
                'amount' => $amount,
                'reason' => $reason,
                'reference' => $reference,
                'balance_after' => $wallet->balance,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);

            Log::channel('payments')->info('Wallet credited (Audit Ready)', [
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'reference' => $reference,
            ]);
        });
    }

    /**
     * Списать средства с кошелька.
     * 
     * @throws Exception Если недостаточно средств
     */
    public function debit(User $user, float $amount, string $reason, string $reference = null): void
    {
        DB::transaction(function () use ($user, $amount, $reason, $reference) {
            if ($amount <= 0) {
                throw new Exception('Amount must be greater than 0');
            }

            // Заблокировать для избежания race conditions
            $wallet = $user->wallet()->lockForUpdate()->first();

            if (!$wallet || $wallet->balance < $amount) {
                throw new Exception('Insufficient funds. Balance: ' . ($wallet->balance ?? 0) . ', Required: ' . $amount);
            }

            // Уменьшить баланс
            $wallet->decrement('balance', $amount);

            // Логировать операцию
            $user->ledger()->create([
                'type' => 'debit',
                'amount' => $amount,
                'reason' => $reason,
                'reference' => $reference,
                'balance_after' => $wallet->balance - $amount,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);

            Log::info('Wallet debited', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reason' => $reason,
                'reference' => $reference,
                'balance_after' => $wallet->balance - $amount,
            ]);
        });
    }

    /**
     * Трансфер средств между пользователями.
     */
    public function transfer(User $from, User $to, float $amount, string $reason): void
    {
        DB::transaction(function () use ($from, $to, $amount, $reason) {
            $correlationId = \Illuminate\Support\Str::uuid()->toString();

            // Спишем с отправителя
            $this->debit($from, $amount, "$reason (send to user {$to->id})", $correlationId);

            // Зачислим получателю
            $this->credit($to, $amount, "$reason (receive from user {$from->id})", $correlationId);

            Log::info('Wallet transfer completed', [
                'from_user_id' => $from->id,
                'to_user_id' => $to->id,
                'amount' => $amount,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Получить баланс кошелька.
     */
    public function getBalance(User $user): float
    {
        return $user->wallet->balance ?? 0;
    }

    /**
     * Проверить достаточность средств.
     */
    public function hasBalance(User $user, float $amount): bool
    {
        return $this->getBalance($user) >= $amount;
    }

    /**
     * Получить историю операций.
     */
    public function getLedger(User $user, int $limit = 50)
    {
        return $user->ledger()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Заморозить средства (для холда при платеже).
     */
    public function freeze(User $user, float $amount, string $reason): void
    {
        DB::transaction(function () use ($user, $amount, $reason) {
            $wallet = $user->wallet()->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                throw new Exception('Insufficient funds for freeze');
            }

            // Создать freeze record
            $user->walletFreezes()->create([
                'amount' => $amount,
                'reason' => $reason,
                'correlation_id' => \Illuminate\Support\Str::uuid(),
            ]);

            Log::info('Wallet funds frozen', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Разморозить средства.
     */
    public function unfreeze(string $freezeId, bool $apply = false): void
    {
        $freeze = DB::table('wallet_freezes')->find($freezeId);

        if (!$freeze) {
            throw new Exception("Freeze {$freezeId} not found");
        }

        if ($apply) {
            // Если apply=true, списываем средства
            $user = User::find($freeze->user_id);
            $this->debit($user, $freeze->amount, "Freeze applied: {$freeze->reason}", $freezeId);
        }

        DB::table('wallet_freezes')->where('id', $freezeId)->delete();

        Log::info('Wallet freeze released', [
            'freeze_id' => $freezeId,
            'applied' => $apply,
        ]);
    }

    /**
     * Получить инф о кошельке.
     */
    public function getInfo(User $user): array
    {
        return [
            'user_id' => $user->id,
            'balance' => $this->getBalance($user),
            'currency' => 'RUB',
            'last_transaction_at' => $user->ledger()->latest()->first()?->created_at,
            'total_credited' => $user->ledger()->where('type', 'credit')->sum('amount'),
            'total_debited' => $user->ledger()->where('type', 'debit')->sum('amount'),
        ];
    }
}
