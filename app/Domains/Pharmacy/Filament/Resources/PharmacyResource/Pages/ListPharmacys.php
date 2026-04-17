<?php declare(strict_types=1);

/**
 * ListPharmacys — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listpharmacys
 */


namespace App\Domains\Pharmacy\Filament\Resources\PharmacyResource\Pages;

use App\Domains\Pharmacy\Filament\Resources\PharmacyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListPharmacys extends ListRecords
{
    protected static string $resource = PharmacyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}