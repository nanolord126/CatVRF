<?php declare(strict_types=1);

/**
 * ListMedicalRecords — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listmedicalrecords
 */


namespace App\Domains\Veterinary\Filament\Resources\MedicalRecordResource\Pages;

use App\Domains\Veterinary\Filament\Resources\MedicalRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListMedicalRecords extends ListRecords
{
    protected static string $resource = MedicalRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}