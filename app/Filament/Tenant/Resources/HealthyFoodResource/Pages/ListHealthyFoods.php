<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HealthyFoodResource\Pages;

use App\Filament\Tenant\Resources\HealthyFoodResource;
use Filament\Resources\Pages\ListRecords;

final class ListHealthyFoods extends ListRecords
{
    protected static string $resource = HealthyFoodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
