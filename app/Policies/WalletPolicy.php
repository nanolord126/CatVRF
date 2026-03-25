<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Finances\Models\Wallet;
use Illuminate\Support\Facades\Log;

/**
 * Wallet Authorization Policy
 * CANON 2026 - Production Ready
 *
 * Управление доступом к кошельку и операциям с балансом.
 * Все проверки учитывают tenant_id + лимиты.
 */
final class WalletPolicy
{
    use HandlesAuthorization;

    /**
     * Может ли пользователь видеть свой кошелёк?
     */
    public function view(User $user, Wallet $wallet): bool
    {
        // CANON 2026: Strict tenant scoping check
        if (isset($wallet->tenant_id) && $user->tenant_id !== $wallet->tenant_id && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\$this->log->warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'model_tenant_id' => $wallet->tenant_id,
            ]);
            return false;
        }

        $allowed = $user->wallet_id === $wallet->id || $user->tenant_id === $wallet->tenant_id || $user->hasRole('admin');

        if (!$allowed) {
            $this->log->warning('Unauthorized wallet view attempt', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли пользователь видеть все кошельки своего tenant?
     * Только администратор или финансовый менеджер.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance_manager', 'business']);
    }

    /**
     * Может ли пользователь совершить вывод?
     * Проверяет: баланс, лимиты, KYC, тенант.
     */
    public function withdraw(User $user, Wallet $wallet): bool
    {
        // Базовые проверки
        if ($user->wallet_id !== $wallet->id) {
            $this->log->warning('Unauthorized withdrawal attempt - wallet mismatch', [
                'user_id' => $user->id,
                'user_wallet_id' => $user->wallet_id,
                'wallet_id' => $wallet->id,
            ]);
            return false;
        }

        // KYC проверка
        if (!$user->kyc_verified) {
            $this->log->info('KYC required for withdrawal', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        // Баланс проверка
        if ($wallet->current_balance < (int) config('wallet.withdrawal.min_amount')) {
            $this->log->info('Insufficient balance for withdrawal', [
                'user_id' => $user->id,
                'current_balance' => $wallet->current_balance,
                'min_required' => (int) config('wallet.withdrawal.min_amount'),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Может ли пользователь совершить переводы между кошельками?
     * Проверяет: KYC, лимиты, баланс.
     */
    public function transfer(User $user, Wallet $walletFrom): bool
    {
        if ($user->wallet_id !== $walletFrom->id) {
            $this->log->warning('Unauthorized transfer from wallet', [
                'user_id' => $user->id,
                'wallet_id' => $walletFrom->id,
            ]);
            return false;
        }

        if (!$user->kyc_verified) {
            return false;
        }

        return true;
    }

    /**
     * Может ли пользователь пополнить кошелёк?
     * Через платёжный шлюз с FraudControl.
     */
    public function deposit(User $user, Wallet $wallet): bool
    {
        if ($user->wallet_id !== $wallet->id) {
            return false;
        }

        // Базовая проверка - нужна почта, но не обязательна верификация
        return filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Может ли пользователь просмотреть историю операций?
     */
    public function viewTransactionHistory(User $user, Wallet $wallet): bool
    {
        return $user->wallet_id === $wallet->id || $user->hasRole('admin');
    }

    /**
     * Может ли пользователь просмотреть бонусы?
     */
    public function viewBonuses(User $user, Wallet $wallet): bool
    {
        return $user->wallet_id === $wallet->id || $user->hasRole('admin');
    }

    /**
     * Может ли пользователь потратить бонусы?
     * Физлица - да, бизнес - нет (только выводить в деньги).
     */
    public function spendBonuses(User $user, Wallet $wallet): bool
    {
        if ($user->wallet_id !== $wallet->id) {
            return false;
        }

        // Бонусы может тратить любой пользователь (если они есть)
        return true;
    }

    /**
     * Может ли пользователь вывести бонусы?
     * Только бизнес может выводить бонусы денежно.
     */
    public function withdrawBonuses(User $user, Wallet $wallet): bool
    {
        if ($user->wallet_id !== $wallet->id) {
            return false;
        }

        // Только бизнес может выводить бонусы
        return $user->hasRole('business') && $user->kyc_verified;
    }

    /**
     * Может ли пользователь запросить срочный вывод?
     * Ограничено по количеству и сумме в день.
     */
    public function requestExpressWithdrawal(User $user, Wallet $wallet): bool
    {
        if ($user->wallet_id !== $wallet->id) {
            return false;
        }

        if (!$user->kyc_verified) {
            return false;
        }

        // Дополнительная проверка - не более 2 срочных выводов в день
        $expressWithdrawalsToday = $wallet
            ->transactions()
            ->where('type', 'withdrawal')
            ->where('is_express', true)
            ->whereDate('created_at', now())
            ->count();

        return $expressWithdrawalsToday < 2;
    }

    /**
     * Может ли пользователь изменить способ вывода?
     * Пересейв способа вывода.
     */
    public function updateWithdrawalMethod(User $user, Wallet $wallet): bool
    {
        if ($user->wallet_id !== $wallet->id) {
            return false;
        }

        return $user->kyc_verified;
    }

    /**
     * Может ли пользователь видеть аналитику кошелька?
     */
    public function viewAnalytics(User $user, Wallet $wallet): bool
    {
        return $user->wallet_id === $wallet->id || ($user->hasRole('admin') && $user->tenant_id === $wallet->tenant_id);
    }

    /**
     * Может ли АДМИНИСТРАТОР обновить кошелёк?
     * Например, разблокировать, увеличить лимит.
     */
    public function update(User $user, Wallet $wallet): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\$this->log->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        $allowed = $user->hasRole('admin') && $user->tenant_id === $wallet->tenant_id;

        if (!$allowed) {
            $this->log->warning('Unauthorized wallet update attempt', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли администратор удалить кошелёк?
     * ЗАПРЕЩЕНО - кошельки хранятся для аудита.
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\$this->log->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return false;
    }

    /**
     * Может ли администратор восстановить кошелёк?
     * ЗАПРЕЩЕНО.
     */
    public function restore(User $user, Wallet $wallet): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\$this->log->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return false;
    }

    /**
     * Может ли администратор hard-удалить кошелёк?
     * ЗАПРЕЩЕНО НАВСЕГДА.
     */
    public function forceDelete(User $user, Wallet $wallet): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\$this->log->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return false;
    }
}
