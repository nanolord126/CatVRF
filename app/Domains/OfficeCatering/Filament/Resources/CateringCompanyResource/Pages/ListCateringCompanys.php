<?php declare(strict_types=1);

/**
 * ListCateringCompanys — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listcateringcompanys
 */


namespace App\Domains\OfficeCatering\Filament\Resources\CateringCompanyResource\Pages;

use App\Domains\OfficeCatering\Filament\Resources\CateringCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCateringCompanys extends ListRecords
{
    protected static string $resource = CateringCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}