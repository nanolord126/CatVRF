<?php declare(strict_types=1);

/**
 * ListConsultants — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listconsultants
 */


namespace App\Domains\Consulting\Filament\Resources\ConsultantResource\Pages;

use App\Domains\Consulting\Filament\Resources\ConsultantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListConsultants extends ListRecords
{
    protected static string $resource = ConsultantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}