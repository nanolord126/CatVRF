<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\Pages;

use LoggerInterface;

use Carbon\CarbonImmutable;

use App\Filament\Tenant\Resources\Hotels\HotelsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Psr\Log\LoggerInterface;
use Filament\Actions\Action;

/**
 * ViewHotel — страница просмотра записи отеля.
 *
 * Filament v3 Page: tenant-scoped, correlation_id tracing, audit logging.
 * Без constructor injection — используем app() для получения сервисов.
 */
final class ViewHotel extends ViewRecord
{
    protected static string $resource = HotelsResource::class;

    /**
     * Действия в заголовке страницы просмотра.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Редактировать')
                ->icon('heroicon-m-pencil-square'),
        ];
    }

    /**
     * Действия после загрузки записи.
     *
     * Логирует просмотр записи с correlation_id для аудита.
     */
    protected function afterLoad(): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */->$this->logger->info('Hotel record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid ?? null,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => filament()->auth()->id(),
            'tenant_id' => filament()->getTenant()?->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }
}
