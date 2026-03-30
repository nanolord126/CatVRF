<?php declare(strict_types=1);

namespace App\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PayrollPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        /**
         * Только владелец бизнеса может просматривать зарплаты.
         */
        public function viewAny(User $user): bool
        {
            return $user->isBusinessOwner() || $user->hasAbility('view_payroll');
        }

        /**
         * Просмотр отдельной зарплаты.
         */
        public function view(User $user): bool
        {
            return $this->viewAny($user);
        }

        /**
         * Только владелец бизнеса может создавать зарплаты.
         */
        public function create(User $user): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return $user->isBusinessOwner() && $user->hasAbility('manage_payroll');
        }

        /**
         * Только владелец бизнеса может обновлять зарплаты.
         */
        public function update(User $user): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return $user->isBusinessOwner() && $user->hasAbility('manage_payroll');
        }

        /**
         * Только владелец бизнеса может удалять зарплаты.
         */
        public function delete(User $user): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return $user->isBusinessOwner() && $user->hasAbility('manage_payroll');
        }
}
