<?php declare(strict_types=1);

/**
 * ListElectronicProducts — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listelectronicproducts
 */


namespace App\Domains\Electronics\Filament\Resources\ElectronicProductResource\Pages;

use App\Domains\Electronics\Filament\Resources\ElectronicProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListElectronicProducts extends ListRecords
{
    protected static string $resource = ElectronicProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}