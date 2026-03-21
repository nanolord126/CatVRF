<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\EntertainmentVenueResource\Pages;

use App\Domains\Entertainment\Filament\Resources\EntertainmentVenueResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateEntertainmentVenue extends CreateRecord
{
    protected static string $resource = EntertainmentVenueResource::class;
}
