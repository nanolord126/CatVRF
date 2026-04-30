<?php

declare(strict_types=1);

namespace App\Filament\Resources\VideoRoomResource\Pages;

use App\Filament\Resources\VideoRoomResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListVideoRooms extends ListRecords
{
    protected static string $resource = VideoRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
