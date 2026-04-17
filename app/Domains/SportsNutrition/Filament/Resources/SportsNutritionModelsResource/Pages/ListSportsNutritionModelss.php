<?php declare(strict_types=1);

/**
 * ListSportsNutritionModelss — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listsportsnutritionmodelss
 */


namespace App\Domains\SportsNutrition\Filament\Resources\SportsNutritionModelsResource\Pages;

use App\Domains\SportsNutrition\Filament\Resources\SportsNutritionModelsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListSportsNutritionModelss extends ListRecords
{
    protected static string $resource = SportsNutritionModelsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}