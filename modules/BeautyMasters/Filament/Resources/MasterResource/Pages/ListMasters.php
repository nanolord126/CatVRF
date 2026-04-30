<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\MasterResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListMasters extends ListRecords
{
    protected static string $resource = \Modules\BeautyMasters\Filament\Resources\MasterResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
