<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources\ExoticGroomingSessionResource\Pages;

use Modules\VetGrooming\Filament\Resources\ExoticGroomingSessionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditExoticGroomingSession extends EditRecord
{
    protected static string $resource = ExoticGroomingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
