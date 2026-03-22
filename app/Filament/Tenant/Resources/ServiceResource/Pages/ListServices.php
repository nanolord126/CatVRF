<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ServiceResource\Pages;

use App\Filament\Tenant\Resources\ServiceResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

final class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
