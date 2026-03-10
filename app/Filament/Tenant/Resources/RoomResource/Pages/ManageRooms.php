<?php

namespace App\Filament\Tenant\Resources\RoomResource\Pages;

use App\Filament\Tenant\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRooms extends ManageRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
