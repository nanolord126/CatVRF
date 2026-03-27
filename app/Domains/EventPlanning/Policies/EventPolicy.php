<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Policies;

use App\Models\User;
use App\Domains\EventPlanning\Models\Event;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

/**
 * EventPolicy.
 * Канон 2026: RBAC, Audit Logging, Tenant Scoping.
 * Политики безопасности для управления EventPlanning.
 */
final class EventPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка событий (List/View)
     */
    public function viewAny(User $user): bool
    {
        Log::channel('audit')->info('Security: UI Access - ViewAny Events', [
            'user_id' => $user->id,
            'tenant_id' => tenant()->id,
        ]);

        return $user->hasRole(['admin', 'business_owner', 'event_planner']);
    }

    /**
     * Просмотр конкретного события (Detail)
     */
    public function view(User $user, Event $event): bool
    {
        return $user->tenant_id === $event->tenant_id;
    }

    /**
     * Создание события (Create)
     */
    public function create(User $user): bool
    {
        Log::channel('audit')->info('Security: UI Access - Create Event', [
            'user_id' => $user->id,
            'tenant_id' => tenant()->id,
        ]);

        return $user->hasRole(['admin', 'business_owner', 'event_planner']);
    }

    /**
     * Редактирование события (Edit)
     */
    public function update(User $user, Event $event): bool
    {
        if ($user->tenant_id !== $event->tenant_id) {
            Log::channel('audit')->warning('Security: Cross-tenant edit attempt', [
                'user_id' => $user->id,
                'event_uuid' => $event->uuid,
                'event_tenant' => $event->tenant_id,
            ]);
            return false;
        }

        return $user->hasRole(['admin', 'business_owner', 'event_planner']);
    }

    /**
     * Удаление события (Delete)
     */
    public function delete(User $user, Event $event): bool
    {
        if ($user->tenant_id !== $event->tenant_id) {
            return false;
        }

        // Удаление разрешено только админу или владельцу
        return $user->hasRole(['admin', 'business_owner']);
    }

    /**
     * Отмена события (Special Action)
     */
    public function cancel(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}
