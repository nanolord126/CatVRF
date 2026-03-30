<?php declare(strict_types=1);

namespace App\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ChannelPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        /** Смотреть собственный канал */
        public function view(User $user, BusinessChannel $channel): bool
        {
            return (int) $channel->tenant_id === (int) $user->current_tenant_id;
        }

        /** Создать канал (не более 1 на tenant) */
        public function create(User $user): bool
        {
            return $user->current_tenant_id !== null;
        }

        /** Редактировать канал */
        public function update(User $user, BusinessChannel $channel): bool
        {
            return (int) $channel->tenant_id === (int) $user->current_tenant_id;
        }

        /** Удалить / архивировать канал */
        public function delete(User $user, BusinessChannel $channel): bool
        {
            return (int) $channel->tenant_id === (int) $user->current_tenant_id;
        }

        /** Подписаться на тариф */
        public function subscribeToPlan(User $user, BusinessChannel $channel): bool
        {
            return (int) $channel->tenant_id === (int) $user->current_tenant_id;
        }

        /** Видеть количество подписчиков (только владелец) */
        public function viewSubscribersCount(User $user, BusinessChannel $channel): bool
        {
            return (int) $channel->tenant_id === (int) $user->current_tenant_id;
        }
}
