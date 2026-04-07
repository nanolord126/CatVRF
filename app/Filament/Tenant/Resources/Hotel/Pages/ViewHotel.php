<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotel\Pages;

use App\Filament\Tenant\Resources\Hotels\HotelResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Psr\Log\LoggerInterface;

/**
 * ViewHotel — страница просмотра отеля (Hotel namespace).
 *
 * Filament v3 Page: tenant-scoped, audit logging.
 *
 * @package App\Filament\Tenant\Resources\Hotel\Pages
 */
final class ViewHotel extends ViewRecord
{
    protected static string $resource = HotelResource::class;

    /**
     * Действия в заголовке.
     *
     * @return array<\Filament\Actions\Action>
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
     */
    protected function afterLoad(): void
    {
        app(LoggerInterface::class)->info('Hotel record viewed (Hotel ns)', [
            'record_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => filament()->auth()->id(),
            'tenant_id' => filament()->getTenant()?->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
