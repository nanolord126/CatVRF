<?php declare(strict_types=1);

namespace App\Domains\CarSales\Filament\Resources\CarDealerStorefrontResource\Pages;

use App\Domains\CarSales\Filament\Resources\CarDealerStorefrontResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCarDealerStorefronts extends ListRecords
{
    protected static string $resource = CarDealerStorefrontResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
