<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotoStudioResource\Pages;

use App\Filament\Tenant\Resources\PhotoStudioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhotoStudios extends ListRecords
{
    protected static string $resource = PhotoStudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
