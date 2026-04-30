<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\BrowCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\BrowCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBrowCertifications extends ListRecords
{
    protected static string $resource = BrowCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
