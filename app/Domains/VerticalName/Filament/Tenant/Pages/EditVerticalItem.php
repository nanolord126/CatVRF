<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Filament\Tenant\Pages;

use App\Domains\VerticalName\Filament\Tenant\VerticalItemResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Filament Page: редактирование товара VerticalName через Tenant Panel.
 *
 * CANON 2026 — Layer 9: Filament Tenant Panel.
 * Редактирование существующего товара текущего tenant.
 * Tenant-scoping гарантирует, что чужие товары недоступны.
 *
 * Функциональность:
 *   — Форма редактирования из VerticalItemResource::form().
 *   — Автоматическая подстановка correlation_id перед сохранением.
 *   — Кнопка удаления товара в header.
 *   — Уведомление после успешного сохранения.
 *
 * @package App\Domains\VerticalName\Filament\Tenant\Pages
 */
final class EditVerticalItem extends EditRecord
{
    /**
     * Связанный Filament-ресурс.
     *
     * @var string
     */
    protected static string $resource = VerticalItemResource::class;

    /**
     * Заголовок страницы.
     *
     * @var string|null
     */
    protected ?string $heading = 'Редактировать товар';

    /**
     * Actions в header страницы.
     *
     * Включает кнопку удаления товара с подтверждением.
     * Удалённые товары попадают в SoftDeletes (корзина).
     *
     * @return array<Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Удалить')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Удалить товар')
                ->modalDescription('Товар будет помещён в корзину. Восстановление возможно в течение 90 дней.')
                ->modalSubmitActionLabel('Да, удалить'),
        ];
    }

    /**
     * Мутация данных перед сохранением изменений.
     *
     * Обновляет correlation_id для каждого изменения,
     * чтобы каждая мутация имела уникальный trace ID.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = app(Request::class)->header('X-Correlation-ID', Str::uuid()->toString());

        return $data;
    }

    /**
     * Callback после сохранения записи.
     *
     * Отправляет Filament-уведомление об успешном обновлении.
     */
    protected function afterSave(): void
    {
        Notification::make()
            ->title('Товар обновлён')
            ->body("Изменения в товаре «{$this->record->name}» сохранены.")
            ->success()
            ->send();
    }

    /**
     * URL для редиректа после сохранения.
     *
     * @return string
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
