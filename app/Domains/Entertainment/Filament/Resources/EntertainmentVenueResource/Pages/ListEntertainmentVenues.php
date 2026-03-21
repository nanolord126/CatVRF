<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\EntertainmentVenueResource\Pages;

use App\Domains\Entertainment\Filament\Resources\EntertainmentVenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListEntertainmentVenues extends ListRecords
{
    protected static string $resource = EntertainmentVenueResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
