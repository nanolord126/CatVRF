<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotoSessionResource\Pages;

use App\Filament\Tenant\Resources\PhotoSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhotoSessions extends ListRecords
{
    protected static string $resource = PhotoSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
