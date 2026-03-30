<?php declare(strict_types=1);

namespace App\Domains\Food\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliveryOrderPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): bool
        {
            return true;
        }

        public function view(User $user, DeliveryOrder $delivery): bool
        {
            return $user->id === $delivery->order->client_id || $user->isAdmin();
        }

        public function track(User $user, DeliveryOrder $delivery): Response
        {
            if ($user->id !== $delivery->order->client_id && $user->id !== $delivery->courier_id && !$user->isAdmin()) {
                return $this->response->deny('Вы не можете отслеживать эту доставку');
            }

            return $this->response->allow();
        }
}
