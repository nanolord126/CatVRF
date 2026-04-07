<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\Pages;

use App\Filament\Tenant\Resources\Hotels\HotelsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * EditHotel — страница редактирования записи отеля.
 *
 * Filament v3 Page: tenant-scoped, correlation_id tracing, audit logging.
 * Без constructor injection — используем app() для получения сервисов.
 *
 * @package App\Filament\Tenant\Resources\Hotels\Pages
 */
final class EditHotel extends EditRecord
{
    protected static string $resource = HotelsResource::class;

    /**
     * Действия в заголовке страницы редактирования.
     *
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Удалить')
                ->icon('heroicon-m-trash'),
        ];
    }

    /**
     * Мутация данных формы перед сохранением.
     *
     * Обновляет correlation_id и tenant_id для обеспечения
     * полной трассировки и tenant-scoping при обновлении записи.
     *
     * @param array<string, mixed> $data Данные формы
     * @return array<string, mixed> Обогащённые данные
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = Str::uuid()->toString();
        $data['tenant_id'] = filament()->getTenant()?->id;

        return $data;
    }

    /**
     * Действия после успешного сохранения записи.
     *
     * Логирует факт обновления с correlation_id для аудита.
     */
    protected function afterSave(): void
    {
        app(LoggerInterface::class)->info('Hotel record updated', [
            'record_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => filament()->auth()->id(),
            'tenant_id' => filament()->getTenant()?->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
