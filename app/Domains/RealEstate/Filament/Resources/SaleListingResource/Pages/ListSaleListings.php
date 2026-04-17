<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources\SaleListingResource\Pages;

use App\Domains\RealEstate\Filament\Resources\SaleListingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListSaleListings extends ListRecords
{
    protected static string $resource = SaleListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
