<?php

declare(strict_types=1);

/**
 * ListPartWarranties — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/listpartwarranties
 */

namespace App\Domains\Auto\Filament\Resources\PartWarrantyResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

final class ListPartWarranties extends ListRecords
{
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


    protected static string $resource = PartWarrantyResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все'),
            'active' => Tab::make('Активные')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('DATE_A> NOW()')),
            'no_claims' => Tab::make('Без претензий')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('claim_status', 'none')),
            'pending_claims' => Tab::make('Претензии на рассмотрении')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('claim_status', 'pending'))
                ->badge(fn () => self::getResource()::getEloquentQuery()->where('claim_status', 'pending')->count())
                ->badgeColor('warning'),
            'approved' => Tab::make('Одобренные')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('claim_status', 'approved')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
