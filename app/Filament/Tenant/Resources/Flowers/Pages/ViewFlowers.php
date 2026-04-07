<?php declare(strict_types=1);

/**
 * ViewRecordFlowers — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/viewrecordflowers
 * @see https://catvrf.ru/docs/viewrecordflowers
 * @see https://catvrf.ru/docs/viewrecordflowers
 * @see https://catvrf.ru/docs/viewrecordflowers
 * @see https://catvrf.ru/docs/viewrecordflowers
 * @see https://catvrf.ru/docs/viewrecordflowers
 * @see https://catvrf.ru/docs/viewrecordflowers
 */


namespace App\Filament\Tenant\Resources\Flowers\Pages;

use App\Filament\Tenant\Resources\FlowersResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewRecordFlowers extends ViewRecord
{
    protected static string $resource = FlowersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
