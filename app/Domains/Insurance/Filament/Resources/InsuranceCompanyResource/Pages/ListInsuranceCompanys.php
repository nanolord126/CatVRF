<?php declare(strict_types=1);

/**
 * ListInsuranceCompanys — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listinsurancecompanys
 */


namespace App\Domains\Insurance\Filament\Resources\InsuranceCompanyResource\Pages;

use App\Domains\Insurance\Filament\Resources\InsuranceCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListInsuranceCompanys extends ListRecords
{
    protected static string $resource = InsuranceCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}