<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotographerResource\Pages;

use App\Filament\Tenant\Resources\PhotographerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPhotographer extends EditRecord
{
    protected static string $resource = PhotographerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
