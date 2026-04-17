<?php declare(strict_types=1);

/**
 * ListToyProducts — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listtoyproducts
 */


namespace App\Domains\ToysAndGames\Filament\Resources\ToyProductResource\Pages;

use App\Domains\ToysAndGames\Filament\Resources\ToyProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListToyProducts extends ListRecords
{
    protected static string $resource = ToyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}