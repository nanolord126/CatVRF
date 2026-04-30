<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Filament\Tenant\Pages;

use Illuminate\Notifications\ChannelManager;

use Illuminate\Database\DatabaseManager;

use App\Domains\VerticalName\Filament\Tenant\VerticalItemResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Filament Page: создание товара VerticalName через Tenant Panel.
 *
 * CANON 2026 — Layer 9: Filament Tenant Panel.
 * Создание нового товара для текущего tenant.
 * Все мутации проходят через VerticalItemService (fraud + $this->db->transaction + audit).
 *
 * Функциональность:
 *   — Форма создания из VerticalItemResource::form().
 *   — Перед сохранением: автоматическая подстановка tenant_id и correlation_id.
 *   — После создания: уведомление пользователю + редирект на список.
 */
final class CreateVerticalItem extends CreateRecord
{
    /**
     * Связанный Filament-ресурс.
     */
    protected static string $resource = VerticalItemResource::class;

    /**
     * Заголовок страницы.
     */
    protected readonly ?string $heading = 'Создать товар';

    /**
     * Подзаголовок.
     */
    protected readonly ?string $subheading = 'Заполните все обязательные поля для добавления товара в каталог.';

    /**
     * Мутация данных перед созданием записи.
     *
     * Автоматически добавляет tenant_id, business_group_id и correlation_id.
     * Эти поля никогда не должны заполняться пользователем напрямую.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id;
        $data['business_group_id'] = request()->session()->get('active_business_group_id');
        $data['correlation_id'] = request()->header('X-Correlation-ID', Str::uuid()->toString());

        return $data;
    }

    /**
     * Callback после создания записи.
     *
     * Отправляет Filament-уведомление об успешном создании.
     * Логирование и fraud-check уже выполнены на уровне модели и сервиса.
     */
    protected function afterCreate(): void
    {
        $this->notificationManager->make()
            ->title('Товар создан')
            ->body("Товар «{$this->record->name}» успешно добавлен в каталог.")
            ->success()
            ->send();
    }

    /**
     * URL для редиректа после создания.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
