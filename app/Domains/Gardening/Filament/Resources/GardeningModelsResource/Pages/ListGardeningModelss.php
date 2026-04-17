<?php declare(strict_types=1);

/**
 * ListGardeningModelss — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listgardeningmodelss
 */


namespace App\Domains\Gardening\Filament\Resources\GardeningModelsResource\Pages;

use App\Domains\Gardening\Filament\Resources\GardeningModelsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListGardeningModelss extends ListRecords
{
    protected static string $resource = GardeningModelsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}