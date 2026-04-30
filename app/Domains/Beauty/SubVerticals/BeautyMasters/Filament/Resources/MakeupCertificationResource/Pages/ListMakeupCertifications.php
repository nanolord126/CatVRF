<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\MakeupCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\MakeupCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListMakeupCertifications extends ListRecords
{
    protected static string $resource = MakeupCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
