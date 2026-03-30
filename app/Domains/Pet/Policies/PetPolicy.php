<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        public function view(User $user, Pet $pet): bool
        {
            // Доступ имеет владелец тенанта или сотрудник, привязанный к этой записи
            return $pet->tenant_id === $user->tenant_id;
        }

        public function update(User $user, Pet $pet): bool
        {
            if (!app(\App\Services\FraudControlService::class)->check('update_pet_medical_card', ['pet_id' => $pet->id])) {
                return false;
            }

            return $pet->tenant_id === $user->tenant_id && $user->can('edit_medical_cards');
        }

        public function delete(User $user, Pet $pet): bool
        {
            // Удаление медкарты — критическое действие, логируется отдельно
            return $pet->tenant_id === $user->tenant_id && $user->isAdmin();
        }
}
