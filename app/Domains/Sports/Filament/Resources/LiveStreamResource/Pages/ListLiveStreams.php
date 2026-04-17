<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\LiveStreamResource\Pages;

use App\Domains\Sports\Filament\Resources\LiveStreamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLiveStreams extends ListRecords
{
    protected static string $resource = LiveStreamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
