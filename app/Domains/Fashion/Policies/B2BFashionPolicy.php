<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BFashionPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $user->is_business
                ? $this->response->allow()
                : $this->response->deny('Только для бизнеса');
        }

        public function viewStorefront(User $user): Response
        {
            return $user->is_business
                ? $this->response->allow()
                : $this->response->deny('Только для бизнеса');
        }

        public function createStorefront(User $user): Response
        {
            return $user->is_business && $user->is_verified
                ? $this->response->allow()
                : $this->response->deny('Требуется верификация');
        }

        public function updateStorefront(User $user): Response
        {
            return $user->is_business
                ? $this->response->allow()
                : $this->response->deny('Только для бизнеса');
        }

        public function viewOrder(User $user): Response
        {
            return $user->is_business
                ? $this->response->allow()
                : $this->response->deny('Только для бизнеса');
        }

        public function approveOrder(User $user): Response
        {
            return $user->is_business && $user->is_verified
                ? $this->response->allow()
                : $this->response->deny('Требуется верификация');
        }

        public function rejectOrder(User $user): Response
        {
            return $user->is_business
                ? $this->response->allow()
                : $this->response->deny('Только для бизнеса');
        }

        public function verifyInn(User $user): Response
        {
            return $user->is_admin
                ? $this->response->allow()
                : $this->response->deny('Только администратор');
        }
}
