<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\Pages;

use LoggerInterface;

use Carbon\CarbonImmutable;

use App\Filament\Tenant\Resources\Hotels\HotelsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Filament\Actions\Action;

/**
 * EditHotels — альтернативная страница редактирования записи отеля.
 *
 * Используется ресурсом HotelsResource для маршрута /{record}/edit.
 * Tenant-scoped, correlation_id tracing, audit logging.
 */
final class EditHotels extends EditRecord
{
    protected static string $resource = HotelsResource::class;

    /**
     * Действия в заголовке страницы редактирования.
     *
     * @return array<Action>
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
     * @param  array<string, mixed>  $data  Данные формы
     * @return array<string, mixed> Обогащённые данные
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = Str::uuid()->toString();
        $data['tenant_id'] = filament()->getTenant()?->id;

        return $data;
    }

    /**
     * Действия после успешного сохранения.
     *
     * Логирует факт обновления с correlation_id для аудита.
     */
    protected function afterSave(): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */->$this->logger->info('Hotels record updated', [
            'record_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => filament()->auth()->id(),
            'tenant_id' => filament()->getTenant()?->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }
}
