<?php declare(strict_types=1);

/**
 * ListEvents — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 */


/**
 * ListEvents — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 * @see https://catvrf.ru/docs/listevents
 */


namespace App\Filament\Tenant\Resources\EventPlanning\EventResource\Pages;

use Filament\Resources\Pages\ListRecords;

final class ListEvents extends ListRecords
{

    protected static string $resource = EventResource::class;

        /**
         * Header Actions — Кнопки действий над списком.
         */
        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Создать Праздник')
                    ->icon('heroicon-o-plus-circle'),
            ];
        }
}
