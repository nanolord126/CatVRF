<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources\ViewingAppointmentResource\Pages;

use App\Domains\RealEstate\Filament\Resources\ViewingAppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListViewingAppointments extends ListRecords
{
    protected static string $resource = ViewingAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
