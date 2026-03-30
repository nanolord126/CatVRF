<?php declare(strict_types=1);

namespace App\Domains\Auto\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoServiceOrderPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        public function viewAny(User $user): bool
        {
            return true; // Все могут видеть список заказов (публичная информация)
        }

        public function view(User $user, AutoServiceOrder $order): bool
        {
            return $user->id === $order->client_id || $user->isAdmin();
        }

        public function create(User $user): bool
        {
            return $user->isVerified();
        }

        public function cancel(User $user, AutoServiceOrder $order): Response
        {
            if ($user->id !== $order->client_id && !$user->isAdmin()) {
                return $this->response->deny('Вы не можете отменить этот заказ');
            }

            if ($order->status === 'completed' || $order->status === 'cancelled') {
                return $this->response->deny('Заказ уже завершён или отменён');
            }

            // Отмену можно сделать только в течение 24 часов до начала
            $hoursUntilStart = $order->appointment_datetime->diffInHours(now(), false);
            if ($hoursUntilStart < -24) {
                return $this->response->deny('Отмену можно сделать только за 24 часа до начала');
            }

            return $this->response->allow();
        }
}
