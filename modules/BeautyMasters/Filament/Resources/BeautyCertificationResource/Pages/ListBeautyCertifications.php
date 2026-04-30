<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\BeautyCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\BeautyCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBeautyCertifications extends ListRecords
{
    protected static string $resource = BeautyCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
