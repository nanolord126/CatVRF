<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\LashCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\LashCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListLashCertifications extends ListRecords
{
    protected static string $resource = LashCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
