<?php

declare(strict_types=1);

namespace Modules\Supermarket\Filament\Resources\DocumentResource\Pages;

use Modules\Supermarket\Filament\Resources\DocumentResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
