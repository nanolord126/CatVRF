<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\EntertainmentEventResource\Pages;

use App\Domains\Entertainment\Filament\Resources\EntertainmentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListEntertainmentEvents extends ListRecords
{
    protected static string $resource = EntertainmentEventResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
