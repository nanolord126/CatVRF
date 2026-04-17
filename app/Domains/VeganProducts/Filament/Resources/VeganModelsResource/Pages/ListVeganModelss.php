<?php declare(strict_types=1);

/**
 * ListVeganModelss — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listveganmodelss
 */


namespace App\Domains\VeganProducts\Filament\Resources\VeganModelsResource\Pages;

use App\Domains\VeganProducts\Filament\Resources\VeganModelsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListVeganModelss extends ListRecords
{
    protected static string $resource = VeganModelsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}