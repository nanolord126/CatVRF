<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Filament\Resources\EventResource\Pages;

use App\Domains\EventPlanning\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
