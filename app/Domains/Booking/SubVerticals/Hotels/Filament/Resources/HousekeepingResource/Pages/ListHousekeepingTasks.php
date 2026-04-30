<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\HousekeepingResource\Pages;

use Modules\Hotels\Filament\Resources\HousekeepingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListHousekeepingTasks extends ListRecords
{
    protected static string $resource = HousekeepingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
