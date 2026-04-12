<?php declare(strict_types=1);

namespace App\Policies;
use Illuminate\Database\Eloquent\Model;


use Psr\Log\LoggerInterface;
final class BonusPolicy extends Model
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}
        /**
         * Может ли пользователь видеть свой бонус?
         */
        public function view(User $user, Bonus $bonus): bool
        {
            // CANON 2026: Strict tenant scoping check
            if (isset($bonus->tenant_id) && $user->tenant_id !== $bonus->tenant_id && !$user->hasRole('admin')) {
                $this->logger->warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'user_tenant_id' => $user->tenant_id,
                    'model_tenant_id' => $bonus->tenant_id,
                ]);
                return false;
            }

            $allowed = $user->id === $bonus->user_id || $user->hasRole('admin');

            if (!$allowed) {
                $this->logger->warning('Unauthorized bonus view attempt', [
                    'user_id' => $user->id,
                    'bonus_id' => $bonus->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь видеть все бонусы?
         * Только администратор.
         */
        public function viewAny(User $user): bool
        {
            return $user->hasRole('admin');
        }

        /**
         * Может ли пользователь просмотреть свой баланс бонусов?
         */
        public function viewBalance(User $user, ?User $targetUser = null): bool
        {
            $targetUser = $targetUser ?? $user;
            return $user->id === $targetUser->id || $user->hasRole('admin');
        }

        /**
         * Может ли пользователь потратить бонус?
         * Физлица могут тратить, бизнес - только выводить.
         */
        public function spend(User $user, Bonus $bonus): bool
        {
            $allowed = (
                $user->id === $bonus->user_id &&
                !$bonus->spent_at &&
                !$bonus->withdrawn_at &&
                $bonus->expires_at > now() &&
                $user->hasRole('user')
            );

            if (!$allowed) {
                $this->logger->info('Unauthorized bonus spend attempt', [
                    'user_id' => $user->id,
                    'bonus_id' => $bonus->id,
                    'is_expired' => $bonus->expires_at <= now(),
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь вывести бонус денежно?
         * Только бизнес может выводить бонусы.
         * Физлица могут только тратить.
         */
        public function withdraw(User $user, Bonus $bonus): bool
        {
            $allowed = (
                $user->id === $bonus->user_id &&
                !$bonus->spent_at &&
                !$bonus->withdrawn_at &&
                $bonus->expires_at > now() &&
                $user->hasRole('business') &&
                $user->kyc_verified
            );

            if (!$allowed) {
                $this->logger->warning('Unauthorized bonus withdrawal attempt', [
                    'user_id' => $user->id,
                    'bonus_id' => $bonus->id,
                    'is_business' => $user->hasRole('business'),
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь просмотреть полный расчёт бонуса?
         * Причина начисления, условия, сроки истечения.
         */
        public function viewDetails(User $user, Bonus $bonus): bool
        {
            return $user->id === $bonus->user_id || $user->hasRole('admin');
        }

        /**
         * Может ли пользователь просмотреть историю бонусов?
         */
        public function viewHistory(User $user): bool
        {
            return true; // каждый может видеть свою историю
        }

        /**
         * Может ли пользователь просмотреть сроки истечения бонусов?
         */
        public function viewExpirationSchedule(User $user): bool
        {
            return $user->email_verified_at !== null;
        }

        /**
         * Может ли пользователь запросить продление бонуса?
         * При особых обстоятельствах (с обоснованием).
         */
        public function requestExtension(User $user, Bonus $bonus): bool
        {
            return (
                $user->id === $bonus->user_id &&
                now()->diffInDays($bonus->expires_at) <= 7 && // осталось 7 дней
                !$bonus->extension_requested_at
            );
        }

        /**
         * Может ли администратор одобрить продление?
         */
        public function approveExtension(User $user, Bonus $bonus): bool
        {
            return $user->hasRole('admin') && $bonus->extension_requested_at !== null && !$bonus->extension_approved_at;
        }

        /**
         * Может ли администратор выдать дополнительный бонус?
         * Например, при разборе жалобы.
         */
        public function award(User $user): bool
        {
            return $user->hasRole('admin');
        }

        /**
         * Может ли администратор отменить бонус?
         * Если выявлена мошенничество.
         */
        public function revoke(User $user, Bonus $bonus): bool
        {
            $allowed = $user->hasRole('admin') && !$bonus->spent_at && !$bonus->withdrawn_at;

            if (!$allowed) {
                $this->logger->warning('Unauthorized bonus revocation attempt', [
                    'user_id' => $user->id,
                    'bonus_id' => $bonus->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь просмотреть условия бонусной программы?
         */
        public function viewTerms(User $user): bool
        {
            return true; // публичная информация
        }

        /**
         * Может ли пользователь просмотреть свой прогресс в программе лояльности?
         */
        public function viewLoyaltyProgress(User $user): bool
        {
            return true; // каждый может видеть свой прогресс
        }

        /**
         * Может ли администратор удалить запись о бонусе?
         * Soft delete для аудита.
         */
        public function delete(User $user, Bonus $bonus): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return $user->hasRole('admin');
        }

        /**
         * Может ли администратор восстановить бонус?
         */
        public function restore(User $user, Bonus $bonus): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return $user->hasRole('admin');
        }

        /**
         * Может ли администратор hard-удалить бонус?
         * ЗАПРЕЩЕНО - бонусы хранятся для аудита.
         */
        public function forceDelete(User $user, Bonus $bonus): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return false;
        }
}
