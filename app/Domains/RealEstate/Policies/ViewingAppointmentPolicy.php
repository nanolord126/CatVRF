<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ViewingAppointmentPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(): bool
        {
            return true;
        }

        public function view($user, $appointment): Response
        {
            return $appointment->client_id === $user?->id
                || $appointment->agent_id === $user?->id
                || $user?->is_admin
                ? $this->response->allow()
                : $this->response->deny('Нет прав');
        }

        public function create($user): Response
        {
            return $user ? $this->response->allow() : $this->response->deny('Требуется авторизация');
        }

        public function cancel($user, $appointment): Response
        {
            return $appointment->client_id === $user?->id || $user?->is_admin
                ? $this->response->allow()
                : $this->response->deny('Нет прав');
        }
}
