<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\HotelResource\Pages;

use LoggerInterface;

use Carbon\CarbonImmutable;

use App\Filament\Tenant\Resources\Hotels\HotelResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Psr\Log\LoggerInterface;
use Filament\Actions\Action;

/**
 * ViewHotel — страница просмотра отеля для HotelResource.
 *
 * Filament v3 Page: tenant-scoped, audit logging.
 */
final class ViewHotel extends ViewRecord
{
    protected static string $resource = HotelResource::class;

    /**
     * Действия в заголовке страницы.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    /**
     * Действия после загрузки записи.
     */
    protected function afterLoad(): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */->$this->logger->info('Hotel record viewed via HotelResource', [
            'record_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => filament()->auth()->id(),
            'tenant_id' => filament()->getTenant()?->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }
}
