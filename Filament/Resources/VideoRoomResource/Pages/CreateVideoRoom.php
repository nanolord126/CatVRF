<?php

declare(strict_types=1);

namespace App\Filament\Resources\VideoRoomResource\Pages;

use App\Filament\Resources\VideoRoomResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateVideoRoom extends CreateRecord
{
    protected static string $resource = VideoRoomResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
