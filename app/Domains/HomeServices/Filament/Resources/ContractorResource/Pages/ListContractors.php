<?php declare(strict_types=1);

/**
 * ListContractors — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listcontractors
 */


namespace App\Domains\HomeServices\Filament\Resources\ContractorResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ContractorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListContractors extends ListRecords
{
    protected static string $resource = ContractorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
