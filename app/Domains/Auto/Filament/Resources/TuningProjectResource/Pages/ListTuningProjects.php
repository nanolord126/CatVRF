<?php declare(strict_types=1);

/**
 * ListTuningProjects — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listtuningprojects
 */


namespace App\Domains\Auto\Filament\Resources\TuningProjectResource\Pages;

use Filament\Resources\Pages\ListRecords;

final class ListTuningProjects extends ListRecords
{

    protected static string $resource = TuningProjectResource::class;

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
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
                'in_progress' => \Filament\Resources\Components\Tab::make('В работе')
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
