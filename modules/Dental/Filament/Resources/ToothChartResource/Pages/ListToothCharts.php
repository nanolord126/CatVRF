<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\ToothChartResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Dental\Filament\Resources\ToothChartResource;

final class ListToothCharts extends ListRecords
{
    protected static string $resource = ToothChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
