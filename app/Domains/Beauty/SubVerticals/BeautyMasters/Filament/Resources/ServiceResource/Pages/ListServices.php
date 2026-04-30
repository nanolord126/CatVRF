<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\ServiceResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListServices extends ListRecords
{
    protected static string $resource = \Modules\BeautyMasters\Filament\Resources\ServiceResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
