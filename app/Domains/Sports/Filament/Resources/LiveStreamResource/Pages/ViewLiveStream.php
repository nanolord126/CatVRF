<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\LiveStreamResource\Pages;

use App\Domains\Sports\Filament\Resources\LiveStreamResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLiveStream extends ViewRecord
{
    protected static string $resource = LiveStreamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
