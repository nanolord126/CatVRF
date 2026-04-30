<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Filament\Resources\LogisticsRouteResource\Pages;

use App\Domains\GeoLogistics\Filament\Resources\LogisticsRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditLogisticsRoute extends EditRecord
{
    protected static string $resource = LogisticsRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
