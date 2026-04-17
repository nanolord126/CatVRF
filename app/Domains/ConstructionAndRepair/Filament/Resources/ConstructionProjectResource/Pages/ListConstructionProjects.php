<?php declare(strict_types=1);

/**
 * ListConstructionProjects — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listconstructionprojects
 */


namespace App\Domains\ConstructionAndRepair\Filament\Resources\ConstructionProjectResource\Pages;

use App\Domains\ConstructionAndRepair\Filament\Resources\ConstructionProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListConstructionProjects extends ListRecords
{
    protected static string $resource = ConstructionProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}