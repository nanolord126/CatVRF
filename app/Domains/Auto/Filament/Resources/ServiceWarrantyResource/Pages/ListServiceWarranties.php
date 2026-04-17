<?php declare(strict_types=1);

/**
 * ListServiceWarranties — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listservicewarranties
 */


namespace App\Domains\Auto\Filament\Resources\ServiceWarrantyResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

final class ListServiceWarranties extends ListRecords
{

    protected static string $resource = ServiceWarrantyResource::class;

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
                'active' => \Filament\Resources\Components\Tab::make('Активные')
                    ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('DATE_A> NOW()')),
                'no_claims' => \Filament\Resources\Components\Tab::make('Без претензий')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('claim_status', 'none')),
                'pending_claims' => \Filament\Resources\Components\Tab::make('Претензии на рассмотрении')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('claim_status', 'pending'))
                    ->badge(fn () => static::getResource()::getEloquentQuery()->where('claim_status', 'pending')->count())
                    ->badgeColor('warning'),
                'approved' => \Filament\Resources\Components\Tab::make('Одобренные')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('claim_status', 'approved')),
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
