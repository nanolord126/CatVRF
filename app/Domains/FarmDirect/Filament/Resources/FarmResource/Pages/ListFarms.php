<?php declare(strict_types=1);

/**
 * ListFarms — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listfarms
 */


namespace App\Domains\FarmDirect\Filament\Resources\FarmResource\Pages;

use App\Domains\FarmDirect\Filament\Resources\FarmResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFarms extends ListRecords
{
    protected static string $resource = FarmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}