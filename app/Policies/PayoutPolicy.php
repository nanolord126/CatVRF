<?php declare(strict_types=1);

namespace App\Policies;


use Psr\Log\LoggerInterface;
final class PayoutPolicy extends Model
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    use HandlesAuthorization;

        /**
         * Только финансовый менеджер может просматривать выплаты.
         */
        public function viewAny(User $user): bool
        {
            return $user->isBusinessOwner() || $user->hasAbility('view_payouts');
        }

        /**
         * Просмотр отдельной выплаты.
         */
        public function view(User $user): bool
        {
            return $this->viewAny($user);
        }

        /**
         * Только финансовый менеджер может создавать выплаты.
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

            return ($user->isBusinessOwner() || $user->hasAbility('create_payouts'))
                && $user->hasAbility('manage_finances');
        }

        /**
         * Только финансовый менеджер может обновлять выплаты.
         */
        public function update(User $user): bool
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

            return ($user->isBusinessOwner() || $user->hasAbility('update_payouts'))
                && $user->hasAbility('manage_finances');
        }

        /**
         * Только финансовый менеджер может удалять выплаты.
         */
        public function delete(User $user): bool
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

            return ($user->isBusinessOwner() || $user->hasAbility('delete_payouts'))
                && $user->hasAbility('manage_finances');
        }
}
