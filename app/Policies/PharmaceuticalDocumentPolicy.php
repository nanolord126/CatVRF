<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Pharmaceutical Document Policy
 * 
 * Политика доступа для документов движения лекарственных средств
 * Реализует segregation of duties для ФЗ-61 compliance
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class PharmaceuticalDocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр документов
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pharmaceutical_documents.view');
    }

    /**
     * Просмотр конкретного документа
     */
    public function view(User $user, $document): bool
    {
        return $user->hasPermission('pharmaceutical_documents.view')
            && $this->belongsToTenant($user, $document);
    }

    /**
     * Создание документа
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('pharmaceutical_documents.create');
    }

    /**
     * Обновление документа (только в статусе draft)
     */
    public function update(User $user, $document): bool
    {
        return $user->hasPermission('pharmaceutical_documents.update')
            && $document->status === 'draft'
            && $this->belongsToTenant($user, $document)
            && $this->isCreator($user, $document);
    }

    /**
     * Удаление документа (только в статусе draft)
     */
    public function delete(User $user, $document): bool
    {
        return $user->hasPermission('pharmaceutical_documents.delete')
            && $document->status === 'draft'
            && $this->belongsToTenant($user, $document)
            && $this->isCreator($user, $document);
    }

    /**
     * Подтверждение документа (требует отдельной роли)
     */
    public function confirm(User $user, $document): bool
    {
        // Segregation of duties: создатель не может подтвердить свой документ
        if ($this->isCreator($user, $document)) {
            return false;
        }

        return $user->hasPermission('pharmaceutical_documents.confirm')
            && $document->status === 'draft'
            && $this->belongsToTenant($user, $document);
    }

    /**
     * Отмена документа
     */
    public function cancel(User $user, $document): bool
    {
        return $user->hasPermission('pharmaceutical_documents.cancel')
            && $document->status === 'confirmed'
            && $this->belongsToTenant($user, $document);
    }

    /**
     * Архивирование документов
     */
    public function archive(User $user): bool
    {
        return $user->hasPermission('pharmaceutical_documents.archive');
    }

    /**
     * Проверка принадлежности к tenant
     */
    private function belongsToTenant(User $user, $document): bool
    {
        return $user->tenant_id === $document->tenant_id;
    }

    /**
     * Проверка, является ли пользователь создателем
     */
    private function isCreator(User $user, $document): bool
    {
        return $user->id === $document->created_by;
    }
}
