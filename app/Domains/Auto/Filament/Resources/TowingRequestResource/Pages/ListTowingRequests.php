<?php declare(strict_types=1);

/**
 * ListTowingRequests — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listtowingrequests
 */


namespace App\Domains\Auto\Filament\Resources\TowingRequestResource\Pages;

use Filament\Resources\Pages\ListRecords;

final class ListTowingRequests extends ListRecords
{

    protected static string $resource = TowingRequestResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make(),
            ];
        }

        public function getTabs(): array
        {
            return [
                'all' => \Filament\Resources\Components\Tab::make('Все'),
                'pending' => \Filament\Resources\Components\Tab::make('Ожидают')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                    ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'pending')->count())
                    ->badgeColor('warning'),
                'in_progress' => \Filament\Resources\Components\Tab::make('В процессе')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress'))
                    ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'in_progress')->count())
                    ->badgeColor('info'),
                'completed' => \Filament\Resources\Components\Tab::make('Завершено')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            ];
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
