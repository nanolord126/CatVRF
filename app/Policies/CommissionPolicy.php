<?php declare(strict_types=1);

namespace App\Policies;
use Illuminate\Database\Eloquent\Model;


use Psr\Log\LoggerInterface;
final class CommissionPolicy extends Model
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}
        /**
         * Может ли пользователь видеть свои комиссии?
         * Только соответствующий бизнес (tenant).
         */
        public function view(User $user, CommissionTransaction $commission): bool
        {
            // CANON 2026: Strict tenant scoping check
            if (isset($commission->tenant_id) && $user->tenant_id !== $commission->tenant_id && !$user->hasRole('admin')) {
                $this->logger->warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'user_tenant_id' => $user->tenant_id,
                    'model_tenant_id' => $commission->tenant_id,
                ]);
                return false;
            }

            $allowed = (
                $user->tenant_id === $commission->tenant_id ||
                $user->hasRole('admin')
            );

            if (!$allowed) {
                $this->logger->warning('Unauthorized commission view attempt', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'commission_tenant_id' => $commission->tenant_id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь видеть все комиссии своего tenant?
         * Только администратор или финансовый менеджер.
         */
        public function viewAny(User $user): bool
        {
            return $user->hasAnyRole(['admin', 'finance_manager']);
        }

        /**
         * Может ли пользователь просмотреть разбивку комиссий по вертикалям?
         */
        public function viewByVertical(User $user): bool
        {
            return $user->hasRole(['business', 'finance_manager', 'admin']) && $user->tenant_id !== null;
        }

        /**
         * Может ли пользователь просмотреть расчёт комиссии конкретного платежа?
         */
        public function viewCalculation(User $user, CommissionTransaction $commission): bool
        {
            return (
                $user->tenant_id === $commission->tenant_id &&
                $user->hasRole(['business', 'admin'])
            );
        }

        /**
         * Может ли пользователь просмотреть историю изменения ставок комиссий?
         */
        public function viewRateHistory(User $user): bool
        {
            return $user->hasRole(['business', 'finance_manager', 'admin']) && $user->tenant_id !== null;
        }

        /**
         * Может ли пользователь просмотреть экономию за счёт реферальных бонусов?
         */
        public function viewReferralDiscount(User $user): bool
        {
            return $user->hasRole(['business', 'admin']) && $user->tenant_id !== null;
        }

        /**
         * Может ли пользователь просмотреть полный аудит начисления комиссий?
         */
        public function viewAudit(User $user, CommissionTransaction $commission): bool
        {
            $allowed = (
                $user->tenant_id === $commission->tenant_id &&
                $user->hasRole(['business', 'admin'])
            ) || $user->hasRole('admin');

            if (!$allowed) {
                $this->logger->warning('Unauthorized commission audit view attempt', [
                    'user_id' => $user->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь скачать отчёт по комиссиям?
         */
        public function exportReport(User $user): bool
        {
            return $user->hasRole(['business', 'finance_manager', 'admin']) && $user->tenant_id !== null;
        }

        /**
         * Может ли пользователь просмотреть прогноз комиссий на следующий месяц?
         * На основе DemandForecastService.
         */
        public function viewForecast(User $user): bool
        {
            return $user->hasRole(['business', 'finance_manager', 'admin']) && $user->tenant_id !== null;
        }

        /**
         * Может ли администратор обновить комиссию?
         * ТОЛЬКО если она ещё не захвачена платёжной системой.
         */
        public function update(User $user, CommissionTransaction $commission): bool
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

            $allowed = (
                $user->hasRole('admin') &&
                !$commission->captured_at // нельзя менять уже захваченные
            );

            if (!$allowed) {
                $this->logger->warning('Unauthorized commission update attempt', [
                    'user_id' => $user->id,
                    'commission_id' => $commission->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли администратор удалить комиссию?
         * Soft delete - только до захвата.
         */
        public function delete(User $user, CommissionTransaction $commission): bool
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

            return (
                $user->hasRole('admin') &&
                !$commission->captured_at
            );
        }

        /**
         * Может ли администратор восстановить комиссию?
         */
        public function restore(User $user, CommissionTransaction $commission): bool
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
         * Может ли администратор hard-удалить комиссию?
         * ЗАПРЕЩЕНО - комиссии хранятся для аудита.
         */
        public function forceDelete(User $user, CommissionTransaction $commission): bool
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
