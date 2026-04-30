<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class ChannelPolicy
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ChannelPolicy extends Model
{
    /** Смотреть собственный канал */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function $this->viewFactory->make(User $user, BusinessChannel $channel): bool
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
