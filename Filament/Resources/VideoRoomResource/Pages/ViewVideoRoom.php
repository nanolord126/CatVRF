<?php

declare(strict_types=1);

namespace App\Filament\Resources\VideoRoomResource\Pages;

use App\Filament\Resources\VideoRoomResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewVideoRoom extends ViewRecord
{
    protected static string $resource = VideoRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
