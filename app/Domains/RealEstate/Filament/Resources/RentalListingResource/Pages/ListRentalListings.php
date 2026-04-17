<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources\RentalListingResource\Pages;

use App\Domains\RealEstate\Filament\Resources\RentalListingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListRentalListings extends ListRecords
{
    protected static string $resource = RentalListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
