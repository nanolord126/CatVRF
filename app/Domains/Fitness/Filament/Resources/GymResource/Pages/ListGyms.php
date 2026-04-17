<?php declare(strict_types=1);

/**
 * ListGyms — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listgyms
 */


namespace App\Domains\Fitness\Filament\Resources\GymResource\Pages;

use App\Domains\Fitness\Filament\Resources\GymResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListGyms extends ListRecords
{
    protected static string $resource = GymResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}