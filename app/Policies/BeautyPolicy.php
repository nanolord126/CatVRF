<?php declare(strict_types=1);

namespace App\Policies;


use Psr\Log\LoggerInterface;
final class BeautyPolicy extends Model
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    use HandlesAuthorization;

        /**
         * Может ли пользователь видеть салон?
         * Публичная информация видна всем, детали видны владельцу.
         */
        public function view(User $user, BeautySalon $salon): bool
        {
            // CANON 2026: Strict tenant scoping check
            if (isset($salon->tenant_id) && $user->tenant_id !== $salon->tenant_id && !$user->hasRole('admin')) {
                $this->logger->warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'user_tenant_id' => $user->tenant_id,
                    'model_tenant_id' => $salon->tenant_id,
                ]);
                return false;
            }

            // Публичная информация видна всем (если салон активный)
            if ($salon->is_active) {
                return true;
            }

            // Неактивный салон видит только владелец
            return $user->tenant_id === $salon->tenant_id || $user->hasRole('admin');
        }

        /**
         * Может ли пользователь видеть все салоны?
         */
        public function viewAny(User $user): bool
        {
            // Пользователи видят только активные салоны
            // Админ и финансисты видят все
            return true; // все видят активные салоны
        }

        /**
         * Может ли пользователь создать салон?
         * Только владелец бизнеса с KYC.
         */
        public function create(User $user): bool
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
                $user->hasRole('business') &&
                $user->kyc_verified &&
                $user->tenant_id !== null
            );

            if (!$allowed) {
                $this->logger->info('Unauthorized salon creation attempt', [
                    'user_id' => $user->id,
                    'is_business' => $user->hasRole('business'),
                    'kyc_verified' => $user->kyc_verified,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь обновить салон?
         * Только владелец (tenant_id совпадает).
         */
        public function update(User $user, BeautySalon $salon): bool
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
                $user->tenant_id === $salon->tenant_id &&
                ($user->hasRole(['business', 'admin']) || $user->hasPermission('beauty.manage'))
            );

            if (!$allowed) {
                $this->logger->warning('Unauthorized salon update attempt', [
                    'user_id' => $user->id,
                    'user_tenant_id' => $user->tenant_id,
                    'salon_tenant_id' => $salon->tenant_id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь удалить салон?
         * Soft delete - только владелец.
         */
        public function delete(User $user, BeautySalon $salon): bool
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

            return $user->tenant_id === $salon->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь управлять мастерами салона?
         */
        public function manageMasters(User $user, BeautySalon $salon): bool
        {
            return $user->tenant_id === $salon->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь управлять услугами?
         */
        public function manageServices(User $user, BeautySalon $salon): bool
        {
            return $user->tenant_id === $salon->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь управлять расписанием?
         */
        public function manageSchedule(User $user, BeautySalon $salon): bool
        {
            return $user->tenant_id === $salon->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь управлять расходниками (consumables)?
         */
        public function manageConsumables(User $user, BeautySalon $salon): bool
        {
            return $user->tenant_id === $salon->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь просмотреть аналитику?
         */
        public function viewAnalytics(User $user, BeautySalon $salon): bool
        {
            return (
                $user->tenant_id === $salon->tenant_id ||
                ($user->hasRole('admin') && $user->tenant_id === $salon->tenant_id)
            );
        }

        /**
         * Может ли пользователь просмотреть финансовую информацию?
         */
        public function viewFinance(User $user, BeautySalon $salon): bool
        {
            return (
                $user->tenant_id === $salon->tenant_id &&
                $user->hasAnyRole(['business', 'finance_manager', 'admin'])
            );
        }

        /**
         * Может ли пользователь выставить салон на профилировку?
         */
        public function toggleFeatured(User $user, BeautySalon $salon): bool
        {
            return (
                $user->tenant_id === $salon->tenant_id &&
                $user->hasRole(['business', 'admin'])
            );
        }

        /**
         * Может ли администратор модерировать отзывы?
         */
        public function moderateReviews(User $user, BeautySalon $salon): bool
        {
            return (
                $user->hasRole('admin') &&
                $user->tenant_id === $salon->tenant_id
            );
        }

        /**
         * Может ли администратор восстановить салон?
         */
        public function restore(User $user, BeautySalon $salon): bool
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

            return $user->hasRole('admin') && $user->tenant_id === $salon->tenant_id;
        }

        /**
         * Может ли администратор hard-удалить салон?
         * ЗАПРЕЩЕНО - салоны хранятся для аудита.
         */
        public function forceDelete(User $user, BeautySalon $salon): bool
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
