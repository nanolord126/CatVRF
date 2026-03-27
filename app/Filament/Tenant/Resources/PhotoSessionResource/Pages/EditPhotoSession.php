<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotoSessionResource\Pages;

use App\Filament\Tenant\Resources\PhotoSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPhotoSession extends EditRecord
{
    protected static string $resource = PhotoSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
