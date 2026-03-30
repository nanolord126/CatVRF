<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MortgageApplicationPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny($user): bool
        {
            return $user?->is_admin || false;
        }

        public function view($user, $application): Response
        {
            return $application->client_id === $user?->id || $user?->is_admin
                ? $this->response->allow()
                : $this->response->deny('Нет прав');
        }

        public function create($user): Response
        {
            return $user ? $this->response->allow() : $this->response->deny('Требуется авторизация');
        }

        public function update($user, $application): Response
        {
            return $user?->is_admin ? $this->response->allow() : $this->response->deny('Только админ');
        }
}
