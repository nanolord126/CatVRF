<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotel\Pages;

use LoggerInterface;

use Carbon\CarbonImmutable;

use App\Filament\Tenant\Resources\Hotels\HotelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Filament\Actions\Action;

/**
 * EditHotel — страница редактирования отеля (Hotel namespace).
 *
 * Filament v3 Page: tenant-scoped, correlation_id tracing, audit logging.
 */
final class EditHotel extends EditRecord
{
    protected static string $resource = HotelResource::class;

    /**
     * Действия в заголовке.
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
     * Действия после сохранения.
     */
    protected function afterSave(): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */->$this->logger->info('Hotel record updated (Hotel ns)', [
            'record_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => filament()->auth()->id(),
            'tenant_id' => filament()->getTenant()?->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }
}
