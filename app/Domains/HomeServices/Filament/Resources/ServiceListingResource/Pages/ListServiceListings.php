<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceListingResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceListingResource;
use Filament\Resources\Pages\ListRecords;

final class ListServiceListings extends ListRecords
{
    protected static string $resource = ServiceListingResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
