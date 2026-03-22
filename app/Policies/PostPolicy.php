<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Domains\Channels\Models\Post;
use Illuminate\Auth\Access\HandlesAuthorization;

final class PostPolicy
{
    use HandlesAuthorization;

    /** Видеть пост (публичный или владелец) */
    public function view(?User $user, Post $post): bool
    {
        if ($post->isPublished()) {
            return true;
        }

        return $user !== null && (int) $post->tenant_id === (int) $user->current_tenant_id;
    }

    /** Создать пост — только владелец канала */
    public function create(User $user): bool
    {
        return $user->current_tenant_id !== null;
    }

    /** Редактировать пост — только владелец, только если не published */
    public function update(User $user, Post $post): bool
    {
        if ((int) $post->tenant_id !== (int) $user->current_tenant_id) {
            return false;
        }

        return in_array($post->status, ['draft', 'pending_moderation', 'rejected'], true);
    }

    /** Удалить пост */
    public function delete(User $user, Post $post): bool
    {
        if ((int) $post->tenant_id === (int) $user->current_tenant_id) {
            return true;
        }

        // Администратор платформы
        return $user->hasRole('admin');
    }

    /** Опубликовать пост — владелец (если без модерации) или модератор */
    public function publish(User $user, Post $post): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('moderator')) {
            return true;
        }

        // Если модерация отключена — владелец публикует сам
        if (! config('channels.moderation.enabled', true)) {
            return (int) $post->tenant_id === (int) $user->current_tenant_id;
        }

        return false;
    }

    /** Отклонить пост — только admin/moderator */
    public function reject(User $user, Post $post): bool
    {
        return $user->hasRole('admin') || $user->hasRole('moderator');
    }

    /** Архивировать пост — владелец или admin */
    public function archive(User $user, Post $post): bool
    {
        return (int) $post->tenant_id === (int) $user->current_tenant_id
            || $user->hasRole('admin');
    }
}
